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

    public function __construct(private readonly CartService $cartService)
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

        $request->session()->put(self::SESSION_SELECTED_ADDRESS, (int) $address->id);
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

    public function placeOrder(PlaceOrderRequest $request): RedirectResponse
    {
        $addresses = $this->savedAddresses($request->user());
        $requestedAddressId = (int) $request->input('address_id', 0);
        $selectedAddress = $requestedAddressId > 0
            ? $this->ownedAddress($request->user(), UserAddress::query()->findOrFail($requestedAddressId))
            : $this->resolveSelectedAddress($request, $addresses);
        $summary = $this->buildCheckoutSummary($this->cartService->getCart(), $selectedAddress);

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

        try {
            $order = DB::transaction(function () use ($request, $selectedAddress) {
                $lockedCart = $this->cartService->getCart(true);
                $lockedSummary = $this->buildCheckoutSummary($lockedCart, $selectedAddress);

                if (empty($lockedSummary['cart']['items'])) {
                    throw ValidationException::withMessages([
                        'cart' => 'Your cart is empty. Add products before continuing to payment.',
                    ]);
                }

                if (! $lockedSummary['canProceed']) {
                    throw ValidationException::withMessages([
                        'address' => 'Choose a valid delivery address before continuing to payment.',
                    ]);
                }

                $cartRows = $this->cartService->currentCartQuery()
                    ->with(['product' => function ($query) {
                        $query->with(['product_images']);
                    }])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $user = $request->user();

                $order = Order::query()->create($this->buildOrderAttributes(
                    $user,
                    $selectedAddress,
                    $lockedSummary,
                    $request->validated()['payment_method']
                ));

                $order->items()->createMany($this->buildOrderItemsPayload($cartRows));

                if ($cartRows->isNotEmpty()) {
                    $this->cartService->currentCartQuery()
                        ->whereKey($cartRows->pluck('id')->all())
                        ->delete();
                }

                $this->clearCheckoutState($request);

                return $order;
            });
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

        return redirect()
            ->route('user.checkout.success', ['order' => $order->id], false)
            ->with('checkout_success', 'Your cash on delivery order has been placed successfully.');
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
        $grandTotal = round((float) ($cart['total'] ?? 0) + $shippingAmount, 2);

        $addressHasRequiredFields = $selectedAddress !== null
            && filled($selectedAddress->recipient_name)
            && filled($selectedAddress->recipient_phone)
            && filled($selectedAddress->pincode);

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
            'selectedAddress' => $selectedAddress,
            'selectedAddressId' => $selectedAddress?->id,
            'previewOnly' => $previewOnly,
            'previewPincode' => $previewPincode,
            'shippingQuote' => $shippingQuote,
            'shippingAmount' => $shippingAmount,
            'grandTotal' => $grandTotal,
            'canProceed' => $canProceed,
            'statusTone' => $status['tone'],
            'statusMessage' => $status['message'],
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

    protected function buildOrderAttributes(
        User $user,
        UserAddress $selectedAddress,
        array $summary,
        string $paymentMethod
    ): array {
        $shippingQuote = $summary['shippingQuote'] ?? [];

        return [
            'user_id' => $user->id,
            'user_address_id' => $selectedAddress->id,
            'order_uuid' => (string) Str::uuid(),
            'order_number' => $this->generateOrderNumber(),
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'order_status' => 'placed',
            'currency' => 'KSH',
            'items_count' => collect($summary['cartItems'] ?? [])->sum('qty'),
            'subtotal_amount' => round((float) ($summary['cart']['subtotal'] ?? 0), 2),
            'discount_amount' => round((float) ($summary['cart']['discount'] ?? 0), 2),
            'wallet_applied_amount' => round((float) ($summary['cart']['wallet_applied'] ?? 0), 2),
            'shipping_amount' => round((float) ($summary['shippingAmount'] ?? 0), 2),
            'grand_total' => round((float) ($summary['grandTotal'] ?? 0), 2),
            'address_label' => $selectedAddress->label,
            'recipient_name' => (string) $selectedAddress->recipient_name,
            'recipient_phone' => (string) $selectedAddress->recipient_phone,
            'email' => $user->email,
            'country' => (string) $selectedAddress->country,
            'county' => $selectedAddress->county,
            'sub_county' => $selectedAddress->sub_county,
            'address_line1' => (string) $selectedAddress->address_line1,
            'address_line2' => $selectedAddress->address_line2,
            'estate' => $selectedAddress->estate,
            'landmark' => $selectedAddress->landmark,
            'pincode' => (string) $selectedAddress->pincode,
            'shipping_zone' => $shippingQuote['zone'] ?? null,
            'shipping_eta' => $shippingQuote['eta'] ?? null,
            'shipping_quote' => $shippingQuote,
            'placed_at' => now(),
        ];
    }

    protected function buildOrderItemsPayload(Collection $cartRows): array
    {
        return $cartRows->map(function ($cartRow) {
            $product = $cartRow->product;
            $image = $product && filled($product->main_image)
                ? asset('product-image/medium/' . $product->main_image)
                : asset('front/images/products/no-image.jpg');

            return [
                'product_id' => $product?->id,
                'product_name' => $product?->product_name ?: 'Product',
                'product_code' => $product?->product_code,
                'product_url' => $product?->product_url,
                'product_image' => $image,
                'size' => $cartRow->product_size,
                'color' => $cartRow->product_color,
                'quantity' => (int) $cartRow->product_qty,
                'unit_price' => round((float) $this->resolveOrderItemUnitPrice($cartRow), 2),
                'line_total' => round((float) $this->resolveOrderItemUnitPrice($cartRow) * (int) $cartRow->product_qty, 2),
            ];
        })->values()->all();
    }

    protected function resolveOrderItemUnitPrice($cartRow): float
    {
        $product = $cartRow->product;
        $size = (string) ($cartRow->product_size ?? 'NA');

        if ($product && $size !== '' && strtoupper($size) !== 'NA') {
            $attributePrice = $product::getAttributePrice($product->id, $size);

            if (($attributePrice['status'] ?? false) === true) {
                return (float) ($attributePrice['final_price'] ?? $attributePrice['product_price'] ?? 0);
            }
        }

        return (float) ($product?->final_price ?? $product?->product_price ?? 0);
    }

    protected function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'SHR-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    protected function clearCheckoutState(Request $request): void
    {
        $request->session()->forget([
            'applied_coupon',
            'applied_coupon_id',
            'applied_coupon_discount',
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
