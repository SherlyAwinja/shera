<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\CheckoutAddressRequest;
use App\Http\Requests\Front\PlaceOrderRequest;
use App\Models\Country;
use App\Models\Order;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Front\CartService;
use App\Services\Front\CheckoutService;
use App\Services\Front\WalletService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutController extends Controller
{
    private const SESSION_SELECTED_ADDRESS = 'checkout_selected_address_id';

    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
        private readonly WalletService $walletService
    )
    {
    }

    public function index(Request $request): View
    {
        $cart = $this->cartService->getCart();
        $addresses = $this->savedAddresses($request->user());
        $selectedAddress = $this->resolveSelectedAddress($request, $addresses);
        $summary = $this->buildCheckoutSummary($cart, $selectedAddress);

        return view('front.checkout.index', [
            'cart' => $cart,
            'addresses' => $addresses,
            'selectedAddress' => $selectedAddress,
            'selectedAddressId' => $selectedAddress?->id,
            'summary' => $summary,
            'countries' => $this->countryOptions(),
        ]);
    }

    public function addAddress(CheckoutAddressRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $address = null;

        DB::transaction(function () use ($request, $user, &$address) {
            $address = $user->addresses()->create($request->addressAttributes());

            $shouldBeDefault = $request->shouldMakeDefault() || ! $user->addresses()->whereKeyNot($address->id)->exists();

            if ($shouldBeDefault) {
                $this->markAddressAsDefault($user, $address);
                $address = $address->fresh();
            }
        });

        $request->session()->put(self::SESSION_SELECTED_ADDRESS, (int) ($address->id ?? 0));
        $payload = $this->checkoutStatePayload($request, $user);

        if ($request->expectsJson()) {
            return response()->json(array_merge($payload, [
                'status' => true,
                'message' => 'The new delivery address has been added.',
            ]));
        }

        return redirect()
            ->route('user.checkout.index')
            ->with('checkout_success', 'The new delivery address has been added.');
    }

    public function updateAddress(CheckoutAddressRequest $request, UserAddress $address): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $address = $this->ownedAddress($user, $address);

        DB::transaction(function () use ($request, $user, $address) {
            $address->fill($request->addressAttributes());
            $address->save();

            if ($address->is_default || $request->shouldMakeDefault()) {
                $this->markAddressAsDefault($user, $address);
            }
        });

        $payload = $this->checkoutStatePayload($request, $user);

        if ($request->expectsJson()) {
            return response()->json(array_merge($payload, [
                'status' => true,
                'message' => 'The delivery address has been updated.',
            ]));
        }

        return redirect()
            ->route('user.checkout.index')
            ->with('checkout_success', 'The delivery address has been updated.');
    }

    public function destroyAddress(Request $request, UserAddress $address): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $address = $this->ownedAddress($user, $address);
        $selectedAddressId = (int) $request->session()->get(self::SESSION_SELECTED_ADDRESS, 0);

        DB::transaction(function () use ($user, $address) {
            $wasDefault = $address->is_default;
            $address->delete();

            if (! $wasDefault) {
                return;
            }

            $replacement = $user->addresses()
                ->latest('updated_at')
                ->first();

            if ($replacement) {
                $this->markAddressAsDefault($user, $replacement);
                return;
            }

            $this->syncUserProfileAddress($user, null);
        });

        if ($selectedAddressId === (int) $address->id) {
            $request->session()->forget(self::SESSION_SELECTED_ADDRESS);
        }

        $payload = $this->checkoutStatePayload($request, $user);

        if ($request->expectsJson()) {
            return response()->json(array_merge($payload, [
                'status' => true,
                'message' => 'The delivery address has been deleted.',
            ]));
        }

        return redirect()
            ->route('user.checkout.index')
            ->with('checkout_success', 'The delivery address has been deleted.');
    }

    public function selectAddress(Request $request, UserAddress $address): JsonResponse
    {
        $address = $this->ownedAddress($request->user(), $address);
        $request->session()->put(self::SESSION_SELECTED_ADDRESS, (int) $address->id);

        $summary = $this->buildCheckoutSummary($this->cartService->getCart(), $address);

        return response()->json([
            'status' => true,
            'message' => 'Delivery address updated.',
            'selected_address_id' => $address->id,
            'summary_html' => $this->renderSummary($summary),
            'summary' => $summary,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => ['nullable', 'integer', 'exists:user_addresses,id'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'preview_only' => ['nullable', 'boolean'],
        ]);

        $previewOnly = (bool) ($validated['preview_only'] ?? false);
        $addresses = $this->savedAddresses($request->user());
        $selectedAddress = $this->resolveSelectedAddress($request, $addresses);

        if (! $previewOnly && ! empty($validated['address_id'])) {
            $selectedAddress = $this->ownedAddress(
                $request->user(),
                UserAddress::query()->findOrFail((int) $validated['address_id'])
            );
        }

        $summary = $this->buildCheckoutSummary(
            $this->cartService->getCart(),
            $selectedAddress,
            $validated['pincode'] ?? null,
            $previewOnly
        );

        return response()->json([
            'status' => true,
            'selected_address_id' => $selectedAddress?->id,
            'summary_html' => $this->renderSummary($summary),
            'summary' => $summary,
        ]);
    }

    public function applyWallet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_amount' => ['required', 'numeric', 'min:0.01'],
            'address_id' => ['nullable', 'integer', 'exists:user_addresses,id'],
        ]);

        $addresses = $this->savedAddresses($request->user());
        $selectedAddress = ! empty($validated['address_id'])
            ? $this->ownedAddress(
                $request->user(),
                UserAddress::query()->findOrFail((int) $validated['address_id'])
            )
            : $this->resolveSelectedAddress($request, $addresses);
        $summary = $this->buildCheckoutSummary($this->cartService->getCart(), $selectedAddress);

        if (empty($summary['cartItems'])) {
            return $this->walletSummaryResponse(
                $summary,
                $selectedAddress,
                false,
                'Your cart is empty. Add products before applying wallet credit.'
            );
        }

        if (! ($summary['walletControlsEnabled'] ?? false)) {
            return $this->walletSummaryResponse(
                $summary,
                $selectedAddress,
                false,
                'Choose a valid delivery address before applying wallet credit.'
            );
        }

        $walletBalance = round((float) ($summary['cart']['wallet_balance'] ?? 0), 2);
        $payableBeforeWalletTotal = round((float) ($summary['payableBeforeWalletTotal'] ?? 0), 2);

        if ($walletBalance <= 0) {
            return $this->walletSummaryResponse(
                $summary,
                $selectedAddress,
                false,
                'No active wallet balance is available on your account.'
            );
        }

        if ($payableBeforeWalletTotal <= 0) {
            $this->clearWalletState($request);

            return $this->walletSummaryResponse(
                $this->buildCheckoutSummary($this->cartService->getCart(), $selectedAddress),
                $selectedAddress,
                false,
                'There is no remaining payable amount to cover with wallet credit.'
            );
        }

        $normalized = $this->walletService->normalizeRequestedAmount(
            (float) $validated['wallet_amount'],
            $walletBalance,
            $payableBeforeWalletTotal
        );

        if ($normalized['applied_amount'] <= 0) {
            return $this->walletSummaryResponse(
                $summary,
                $selectedAddress,
                false,
                'Wallet credit cannot be applied to this checkout right now.'
            );
        }

        $request->session()->put('applied_wallet_amount', $normalized['requested_amount']);
        $request->session()->put('applied_wallet_user_id', (int) $request->user()->id);

        $updatedSummary = $this->buildCheckoutSummary($this->cartService->getCart(), $selectedAddress);
        $message = $normalized['was_adjusted']
            ? 'Wallet credit adjusted to ' . $this->walletService->formatAmount((float) ($updatedSummary['cart']['wallet_applied'] ?? 0)) . ' based on your live balance and checkout total.'
            : 'Wallet credit of ' . $this->walletService->formatAmount((float) ($updatedSummary['cart']['wallet_applied'] ?? 0)) . ' applied to this checkout.';

        return $this->walletSummaryResponse($updatedSummary, $selectedAddress, true, $message);
    }

    public function removeWallet(Request $request): JsonResponse
    {
        $addresses = $this->savedAddresses($request->user());
        $selectedAddress = $this->resolveSelectedAddress($request, $addresses);

        $this->clearWalletState($request);

        return $this->walletSummaryResponse(
            $this->buildCheckoutSummary($this->cartService->getCart(), $selectedAddress),
            $selectedAddress,
            true,
            'Wallet credit removed from this checkout.'
        );
    }

    public function placeOrder(PlaceOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $addresses = $this->savedAddresses($request->user());
        $requestedAddressId = (int) $request->input('address_id', 0);
        $selectedAddress = $requestedAddressId > 0
            ? $this->ownedAddress($request->user(), UserAddress::query()->findOrFail($requestedAddressId))
            : $this->resolveSelectedAddress($request, $addresses);
        $summary = $this->buildCheckoutSummary($this->cartService->getCart(), $selectedAddress);
        $selectedPaymentOption = collect($summary['paymentMethods'] ?? [])
            ->firstWhere('code', $validated['payment_method']);

        if (empty($summary['cart']['items'])) {
            return redirect()
                ->route('user.checkout.index')
                ->with('checkout_error', 'Your cart is empty. Add products before continuing to payment.');
        }

        if (! $summary['canProceed']) {
            return redirect()
                ->route('user.checkout.index')
                ->with('checkout_error', 'Choose a valid delivery address before continuing to payment.');
        }

        if (! $selectedPaymentOption || ! ($selectedPaymentOption['enabled'] ?? false)) {
            return redirect()
                ->route('user.checkout.index')
                ->with('checkout_error', 'Choose an available payment method before placing the order.');
        }

        if (! $selectedAddress instanceof UserAddress) {
            return redirect()
                ->route('user.checkout.index')
                ->with('checkout_error', 'Choose a valid delivery address before continuing to payment.');
        }

        try {
            $order = $this->checkoutService->createOrderFromCart(
                $request,
                $selectedAddress,
                $validated,
                fn (array $cart, UserAddress $address): array => $this->buildCheckoutSummary($cart, $address)
            );
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: 'Unable to place your order right now.';

            return redirect()
                ->route('user.checkout.index')
                ->with('checkout_error', $message);
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()
                ->route('user.checkout.index')
                ->with('checkout_error', 'Unable to place your order right now. Please try again.');
        }

        $paymentLabel = $this->paymentMethodLabel($validated['payment_method']);
        $walletAppliedAmount = round((float) $order->wallet_applied_amount, 2);
        $successMessage = $validated['payment_method'] === 'wallet'
            ? 'Your order has been placed successfully and paid fully from your wallet balance.'
            : ($walletAppliedAmount > 0
                ? 'Your order has been placed successfully. Wallet credit of ' . $this->walletService->formatAmount($walletAppliedAmount) . ' was applied and ' . $paymentLabel . ' is set for the remaining balance.'
                : ($validated['payment_method'] === 'cod'
                    ? 'Your cash on delivery order has been placed successfully.'
                    : 'Your order has been placed successfully with ' . $paymentLabel . ' selected as the payment method.'));

        return redirect()
            ->route('user.checkout.success', ['order' => $order->id])
            ->with('checkout_success', $successMessage);
    }

    public function success(Request $request, Order $order): View
    {
        $order = $this->ownedOrder($request->user(), $order->load('items'));

        return view('front.checkout.success', [
            'order' => $order,
        ]);
    }

    protected function savedAddresses(User $user): Collection
    {
        return $user->addresses()
            ->orderByDesc('is_default')
            ->latest('updated_at')
            ->get();
    }

    protected function resolveSelectedAddress(Request $request, Collection $addresses): ?UserAddress
    {
        $selectedId = (int) $request->session()->get(self::SESSION_SELECTED_ADDRESS, 0);
        $selectedAddress = $selectedId > 0
            ? $addresses->firstWhere('id', $selectedId)
            : null;

        if ($selectedAddress) {
            return $selectedAddress;
        }

        if ($selectedId > 0) {
            $request->session()->forget(self::SESSION_SELECTED_ADDRESS);
        }

        $fallback = $addresses->firstWhere('is_default', true) ?: $addresses->first();

        if ($fallback) {
            $request->session()->put(self::SESSION_SELECTED_ADDRESS, (int) $fallback->id);
            return $fallback;
        }

        $request->session()->forget(self::SESSION_SELECTED_ADDRESS);

        return null;
    }

    protected function buildCheckoutSummary(
        array $cart,
        ?UserAddress $selectedAddress = null,
        ?string $previewPincode = null,
        bool $previewOnly = false
    ): array {
        $pincode = $previewPincode ?? $selectedAddress?->pincode;
        $shippingQuote = $this->resolveShippingQuote($pincode, (float) ($cart['subtotal'] ?? 0));
        $shippingAmount = ($shippingQuote['serviceable'] ?? false)
            ? round((float) ($shippingQuote['shipping_amount'] ?? 0), 2)
            : 0.0;
        $payableBeforeWalletTotal = round((float) ($cart['payable_before_wallet'] ?? 0) + $shippingAmount, 2);

        $addressHasRequiredFields = $selectedAddress !== null
            && filled($selectedAddress->recipient_name)
            && filled($selectedAddress->recipient_phone)
            && filled($selectedAddress->pincode);
        $walletControlsEnabled = ! empty($cart['items'])
            && ! $previewOnly
            && $addressHasRequiredFields
            && ($shippingQuote['serviceable'] ?? false);
        $walletState = $this->resolveCheckoutWalletState(
            $cart,
            $walletControlsEnabled ? $payableBeforeWalletTotal : 0.0
        );

        $cart['wallet_balance'] = $walletState['wallet_balance'];
        $cart['wallet_available_to_apply'] = $walletState['wallet_available_to_apply'];
        $cart['requested_wallet_amount'] = $walletState['requested_wallet_amount'];
        $cart['wallet_applied'] = $walletState['wallet_applied'];

        $grandTotal = $walletControlsEnabled
            ? round(max(0, $payableBeforeWalletTotal - $walletState['wallet_applied']), 2)
            : $payableBeforeWalletTotal;
        $cart['remaining_payable'] = $grandTotal;
        $cart['requires_payment_gateway'] = $grandTotal > 0;
        $cart['can_checkout_with_wallet_only'] = $walletControlsEnabled
            && $walletState['wallet_applied'] > 0
            && $grandTotal == 0.0;
        $cart['total'] = $grandTotal;

        $canProceed = ! empty($cart['items'])
            && ! $previewOnly
            && $addressHasRequiredFields
            && ($shippingQuote['serviceable'] ?? false);

        $status = $this->resolveCheckoutStatus(
            $cart,
            $selectedAddress,
            $shippingQuote,
            $previewOnly,
            $previewPincode
        );

        return [
            'cart' => $cart,
            'cartItems' => $cart['items'],
            'paymentMethods' => $this->paymentMethodOptions(
                $cart,
                $payableBeforeWalletTotal,
                $grandTotal,
                $walletControlsEnabled
            ),
            'selectedAddress' => $selectedAddress,
            'selectedAddressId' => $selectedAddress?->id,
            'previewOnly' => $previewOnly,
            'previewPincode' => $previewPincode,
            'shippingQuote' => $shippingQuote,
            'shippingAmount' => $shippingAmount,
            'payableBeforeWalletTotal' => $payableBeforeWalletTotal,
            'grandTotal' => $grandTotal,
            'canProceed' => $canProceed,
            'walletControlsEnabled' => $walletControlsEnabled,
            'statusTone' => $status['tone'],
            'statusMessage' => $status['message'],
        ];
    }

    protected function resolveCheckoutWalletState(array $cart, float $payableBeforeWalletTotal): array
    {
        $walletBalance = round((float) ($cart['wallet_balance'] ?? 0), 2);
        $requestedWalletAmount = round((float) ($cart['requested_wallet_amount'] ?? 0), 2);
        $walletAvailableToApply = round(min($walletBalance, max($payableBeforeWalletTotal, 0)), 2);
        $walletApplied = 0.0;

        if ($requestedWalletAmount > 0 && $walletAvailableToApply > 0) {
            $walletApplied = round(min($requestedWalletAmount, $walletAvailableToApply), 2);
        }

        return [
            'wallet_balance' => $walletBalance,
            'wallet_available_to_apply' => $walletAvailableToApply,
            'requested_wallet_amount' => $requestedWalletAmount,
            'wallet_applied' => $walletApplied,
        ];
    }

    protected function resolveCheckoutStatus(
        array $cart,
        ?UserAddress $selectedAddress,
        array $shippingQuote,
        bool $previewOnly,
        ?string $previewPincode
    ): array {
        if (empty($cart['items'])) {
            return [
                'tone' => 'warning',
                'message' => 'Your cart is empty. Add a few items before continuing to checkout.',
            ];
        }

        if ($previewOnly) {
            if (blank($previewPincode)) {
                return [
                    'tone' => 'info',
                    'message' => 'Enter a pincode to preview shipping before saving the address.',
                ];
            }

            if ($shippingQuote['serviceable'] ?? false) {
                return [
                    'tone' => 'info',
                    'message' => 'Delivery is available for this pincode preview. Save the address to continue to payment.',
                ];
            }

            return [
                'tone' => 'danger',
                'message' => $shippingQuote['message'],
            ];
        }

        if (! $selectedAddress) {
            return [
                'tone' => 'info',
                'message' => 'Choose a delivery address to calculate shipping and continue to payment.',
            ];
        }

        $missingFields = collect([
            blank($selectedAddress->recipient_name) ? 'recipient name' : null,
            blank($selectedAddress->recipient_phone) ? 'phone number' : null,
            blank($selectedAddress->pincode) ? 'pincode' : null,
        ])->filter()->values();

        if ($missingFields->isNotEmpty()) {
            return [
                'tone' => 'danger',
                'message' => 'The selected address is missing ' . $missingFields->implode(', ') . '. Add or choose another address to continue.',
            ];
        }

        if (! ($shippingQuote['serviceable'] ?? false)) {
            return [
                'tone' => 'danger',
                'message' => $shippingQuote['message'],
            ];
        }

        return [
            'tone' => 'success',
            'message' => $shippingQuote['message'],
        ];
    }

    protected function resolveShippingQuote(?string $pincode, float $cartSubtotal): array
    {
        $normalized = preg_replace('/\D+/', '', (string) $pincode);

        if ($normalized === '') {
            return [
                'status' => 'pending',
                'serviceable' => false,
                'pincode' => null,
                'shipping_amount' => 0,
                'eta' => null,
                'zone' => null,
                'free_shipping_applied' => false,
                'message' => 'Select a delivery address or check a pincode to calculate shipping.',
            ];
        }

        if (! preg_match('/^\d{4,10}$/', $normalized)) {
            return [
                'status' => 'invalid',
                'serviceable' => false,
                'pincode' => $normalized,
                'shipping_amount' => 0,
                'eta' => null,
                'zone' => null,
                'free_shipping_applied' => false,
                'message' => 'Enter a valid pincode using 4 to 10 digits.',
            ];
        }

        $prefix = (int) substr($normalized, 0, 2);
        $zone = collect(config('checkout.shipping.zones', []))
            ->first(fn (array $rule) => $prefix >= $rule['min_prefix'] && $prefix <= $rule['max_prefix']);

        if (! $zone) {
            return [
                'status' => 'unserviceable',
                'serviceable' => false,
                'pincode' => $normalized,
                'shipping_amount' => 0,
                'eta' => null,
                'zone' => null,
                'free_shipping_applied' => false,
                'message' => 'We do not currently service deliveries to pincode ' . $normalized . '.',
            ];
        }

        $shippingAmount = (float) ($zone['amount'] ?? 0);
        $freeShippingThreshold = (float) config('checkout.shipping.free_shipping_threshold', 0);
        $freeShippingApplied = $freeShippingThreshold > 0 && $cartSubtotal >= $freeShippingThreshold;

        if ($freeShippingApplied) {
            $shippingAmount = 0.0;
        }

        return [
            'status' => 'serviceable',
            'serviceable' => true,
            'pincode' => $normalized,
            'shipping_amount' => round($shippingAmount, 2),
            'eta' => $zone['eta'] ?? null,
            'zone' => $zone['label'] ?? null,
            'free_shipping_applied' => $freeShippingApplied,
            'message' => $freeShippingApplied
                ? 'Delivery is available to ' . $normalized . '. Free shipping is unlocked and arrival is estimated in ' . ($zone['eta'] ?? '2-4 business days') . '.'
                : 'Delivery is available to ' . $normalized . '. Shipping is KSH.' . number_format($shippingAmount, 2) . ' with an estimated arrival in ' . ($zone['eta'] ?? '2-4 business days') . '.',
        ];
    }

    protected function renderAddressCards(Collection $addresses, ?int $selectedAddressId): string
    {
        return view('front.checkout.partials.address_cards', [
            'addresses' => $addresses,
            'selectedAddressId' => $selectedAddressId,
        ])->render();
    }

    protected function renderSummary(array $summary): string
    {
        return view('front.checkout.partials.order_summary', $summary)->render();
    }

    protected function checkoutStatePayload(Request $request, User $user): array
    {
        $addresses = $this->savedAddresses($user);
        $selectedAddress = $this->resolveSelectedAddress($request, $addresses);
        $summary = $this->buildCheckoutSummary($this->cartService->getCart(), $selectedAddress);

        return [
            'address_count' => $addresses->count(),
            'selected_address_id' => $selectedAddress?->id,
            'addresses_html' => $this->renderAddressCards($addresses, $selectedAddress?->id),
            'summary_html' => $this->renderSummary($summary),
            'summary' => $summary,
        ];
    }

    protected function walletSummaryResponse(
        array $summary,
        ?UserAddress $selectedAddress,
        bool $status,
        string $message,
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'selected_address_id' => $selectedAddress?->id,
            'summary_html' => $this->renderSummary($summary),
            'summary' => $summary,
        ], $statusCode);
    }

    protected function paymentMethodOptions(
        array $cart,
        float $payableBeforeWalletTotal,
        float $grandTotal,
        bool $walletControlsEnabled
    ): array
    {
        $walletBalance = round((float) ($cart['wallet_balance'] ?? 0), 2);
        $walletAvailableToApply = round((float) ($cart['wallet_available_to_apply'] ?? 0), 2);
        $walletApplied = round((float) ($cart['wallet_applied'] ?? 0), 2);
        $walletCoversCheckout = $walletApplied > 0 && $grandTotal == 0.0;

        return collect(config('checkout.payment.methods', []))
            ->map(function (array $method, string $code) use (
                $grandTotal,
                $payableBeforeWalletTotal,
                $walletApplied,
                $walletAvailableToApply,
                $walletBalance,
                $walletControlsEnabled,
                $walletCoversCheckout
            ) {
                $enabled = true;
                $hint = null;

                if ($code === 'wallet') {
                    if (! $walletControlsEnabled) {
                        $enabled = false;
                        $hint = 'Choose a valid delivery address before applying wallet credit or using wallet as the payment method.';
                    } elseif ($walletBalance <= 0) {
                        $enabled = false;
                        $hint = 'No wallet balance is currently available on your account.';
                    } elseif ($walletCoversCheckout) {
                        $hint = 'Wallet credit fully covers this order. You can place it directly with wallet.';
                    } elseif ($walletAvailableToApply >= $payableBeforeWalletTotal) {
                        $enabled = false;
                        $hint = 'Apply the full available wallet balance below to use wallet as the payment method for this order.';
                    } else {
                        $enabled = false;
                        $hint = 'Wallet can cover ' . $this->walletService->formatAmount($walletAvailableToApply) . ' right now. Apply it below, then choose another method for the remaining ' . $this->walletService->formatAmount($grandTotal) . '.';
                    }
                } elseif ($walletCoversCheckout) {
                    $enabled = false;
                    $hint = 'Wallet credit already covers the full order. Select Wallet to place it.';
                } elseif (! $walletControlsEnabled && $grandTotal > 0) {
                    if ($code === 'cod' || $code === 'bank_transfer' || $code === 'card' || $code === 'mobile_wallet' || $code === 'paypal') {
                        $hint = 'Select a valid delivery address to confirm the final payable amount before placing the order.';
                    }
                }

                return array_merge($method, [
                    'code' => $code,
                    'enabled' => $enabled,
                    'hint' => $hint,
                    'show_credit_controls' => $code === 'wallet',
                ]);
            })
            ->values()
            ->all();
    }

    protected function paymentMethodLabel(string $paymentMethod): string
    {
        return (string) data_get(
            config('checkout.payment.methods', []),
            $paymentMethod . '.label',
            Str::headline(str_replace('_', ' ', $paymentMethod))
        );
    }

    protected function clearWalletState(Request $request): void
    {
        $request->session()->forget([
            'applied_wallet_amount',
            'applied_wallet_user_id',
        ]);
    }

    protected function countryOptions(): Collection
    {
        $countries = Country::query()
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN LOWER(name) = 'kenya' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($countries->isNotEmpty()) {
            return $countries;
        }

        return collect(config('locations.country_suggestions', []))
            ->map(fn (array $country) => (object) [
                'id' => null,
                'name' => $country['name'],
            ])
            ->values();
    }

    protected function ownedAddress(User $user, UserAddress $address): UserAddress
    {
        abort_unless($address->user_id === $user->id, 404);

        return $address;
    }

    protected function ownedOrder(User $user, Order $order): Order
    {
        abort_unless($order->user_id === $user->id, 404);

        return $order;
    }

    protected function markAddressAsDefault(User $user, UserAddress $address): void
    {
        $user->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);

        if (! $address->is_default) {
            $address->forceFill(['is_default' => true])->save();
        }

        $this->syncUserProfileAddress($user, $address->fresh());
    }

    protected function syncUserProfileAddress(User $user, ?UserAddress $address): void
    {
        $user->forceFill([
            'address_line1' => $address?->address_line1,
            'address_line2' => $address?->address_line2,
            'country' => $address?->country ?? 'Kenya',
            'county' => $address?->county,
            'sub_county' => $address?->sub_county,
            'estate' => $address?->estate,
            'landmark' => $address?->landmark,
            'phone' => $address?->recipient_phone ?: $user->phone,
        ])->save();
    }
}
