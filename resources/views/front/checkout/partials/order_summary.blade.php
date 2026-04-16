<div class="checkout-summary-card">
@php
        $selectedPaymentMethod = old('payment_method', config('checkout.payment.default_method', 'cod'));
        $walletBalance = (float) ($cart['wallet_balance'] ?? 0);
        $walletAvailableToApply = (float) ($cart['wallet_available_to_apply'] ?? 0);
        $walletApplied = (float) ($cart['wallet_applied'] ?? 0);
        $walletRequestedAmount = (float) ($cart['requested_wallet_amount'] ?? 0);
        $walletAmountValue = $walletApplied > 0 ? $walletRequestedAmount : $walletAvailableToApply;
    @endphp
    <div class="checkout-summary-head">
        <div>
            <span class="checkout-kicker">Order Summary</span>
            <h2 class="checkout-title">Everything in one payment-ready view</h2>
            <p class="checkout-copy">Review products, shipping, discounts, and the final payable amount before moving into the payment step.</p>
        </div>
        <a href="{{ route('cart.index', [], false) }}" class="checkout-chip">
            <i class="fa fa-pen"></i>
            Edit cart
        </a>
    </div>

    <div class="checkout-panel-body">
        <div class="checkout-status" data-tone="{{ $statusTone }}">{{ $statusMessage }}</div>

        @if (!empty($cartItems))
            @if ($previewOnly && $previewPincode)
                <div class="checkout-helper mb-3">Preview mode is using pincode {{ $previewPincode }}. Save the address to enable payment.</div>
            @elseif ($selectedAddress)
                <div class="checkout-helper mb-3">
                    Shipping to {{ $selectedAddress->recipient_name ?: 'selected address' }}
                    @if ($selectedAddress->pincode)
                        | {{ $selectedAddress->pincode }}
                    @endif
                </div>
            @endif

            <div class="checkout-summary-items">
                @foreach ($cartItems as $item)
                    <div class="checkout-summary-item">
                        <img src="{{ $item['image'] }}" alt="{{ $item['product_name'] }}" class="checkout-summary-thumb">
                        <div class="checkout-summary-item-copy">
                            <div class="checkout-address-label mb-1">{{ $item['product_name'] }}</div>
                            <div class="checkout-address-meta mb-2">
                                @if (filled($item['size']) && strtoupper((string) $item['size']) !== 'NA')
                                    <div>Size: {{ $item['size'] }}</div>
                                @endif
                                @if (filled($item['color']))
                                    <div>Color: {{ $item['color'] }}</div>
                                @endif
                                <div>Quantity: {{ $item['qty'] }}</div>
                            </div>
                            <div class="checkout-summary-pricing">
                                <span>Unit: KSH.{{ number_format($item['unit_price'], 2) }}</span>
                                <strong>KSH.{{ number_format($item['line_total'], 2) }}</strong>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="checkout-totals-card">
                <div class="checkout-total-row">
                    <span>Subtotal</span>
                    <strong>KSH.{{ number_format($cart['subtotal'] ?? 0, 2) }}</strong>
                </div>
                <div class="checkout-total-row">
                    <span>Discounts</span>
                    <strong class="{{ ($cart['discount'] ?? 0) > 0 ? 'text-success' : '' }}">
                        {{ ($cart['discount'] ?? 0) > 0 ? '-KSH.' . number_format($cart['discount'], 2) : 'KSH.0.00' }}
                    </strong>
                </div>
                <div class="checkout-total-row">
                    <span>Payable Before Wallet</span>
                    <strong>KSH.{{ number_format($payableBeforeWalletTotal ?? 0, 2) }}</strong>
                </div>
                @if (($cart['wallet_applied'] ?? 0) > 0)
                    <div class="checkout-total-row">
                        <span>Wallet Applied</span>
                        <strong class="text-success">-KSH.{{ number_format($cart['wallet_applied'], 2) }}</strong>
                    </div>
                @endif
                <div class="checkout-total-row">
                    <span>Shipping</span>
                    <strong>
                        @if (($shippingQuote['serviceable'] ?? false) === true)
                            {{ ($shippingQuote['shipping_amount'] ?? 0) > 0 ? 'KSH.' . number_format($shippingQuote['shipping_amount'], 2) : 'Free' }}
                        @elseif (($shippingQuote['status'] ?? '') === 'pending')
                            Pending
                        @else
                            Not available
                        @endif
                    </strong>
                </div>
                @if (!empty($shippingQuote['eta']))
                    <div class="checkout-address-meta mb-3">
                        Estimated delivery: {{ $shippingQuote['eta'] }}
                        @if (!empty($shippingQuote['zone']))
                            | Zone: {{ $shippingQuote['zone'] }}
                        @endif
                    </div>
                @endif
                <div class="checkout-total-row checkout-total-row--grand">
                    <span>Grand Total</span>
                    <strong>KSH.{{ number_format($grandTotal, 2) }}</strong>
                </div>
            </div>

            <form method="POST" action="{{ route('user.checkout.placeOrder', [], false) }}">
                @csrf
                @if ($selectedAddressId)
                    <input type="hidden" name="address_id" value="{{ $selectedAddressId }}">
                @endif
                <div class="checkout-payment-shell">
                    <div class="checkout-payment-head">
                        <span class="checkout-kicker">Payment Method</span>
                        <h3 class="checkout-address-label mb-1">Choose how this order should be paid</h3>
                        <div class="checkout-copy mb-0">
                            Pick the payment route now so the order is recorded with the right settlement preference.
                        </div>
                    </div>

                    <div class="checkout-payment-grid">
                        @foreach (($paymentMethods ?? []) as $method)
                            <div class="checkout-payment-option {{ ($method['show_credit_controls'] ?? false) ? 'checkout-payment-option--wallet' : '' }}">
                                <input
                                    type="radio"
                                    id="checkout_payment_{{ $method['code'] }}"
                                    name="payment_method"
                                    value="{{ $method['code'] }}"
                                    class="checkout-payment-input"
                                    data-payment-method-input
                                    {{ $selectedPaymentMethod === $method['code'] && ($method['enabled'] ?? true) ? 'checked' : '' }}
                                    {{ ($method['enabled'] ?? true) ? '' : 'disabled' }}>
                                <label
                                    for="checkout_payment_{{ $method['code'] }}"
                                    class="checkout-payment-label {{ ($method['enabled'] ?? true) ? '' : 'is-disabled' }}">
                                    <span class="checkout-payment-icon">
                                        <i class="fa {{ $method['icon'] ?? 'fa-credit-card' }}"></i>
                                    </span>
                                    <span class="checkout-payment-copy">
                                        <span class="checkout-payment-top">
                                            <span class="checkout-payment-name">{{ $method['label'] }}</span>
                                            @if (!empty($method['meta']))
                                                <span class="checkout-payment-chip">{{ $method['meta'] }}</span>
                                            @endif
                                        </span>
                                        @if (!empty($method['description']))
                                            <span class="checkout-payment-description">{{ $method['description'] }}</span>
                                        @endif
                                        @if (!empty($method['hint']))
                                            <span class="checkout-payment-hint">{{ $method['hint'] }}</span>
                                        @endif
                                    </span>
                                </label>

                                @if (($method['show_credit_controls'] ?? false) === true)
                                    <div class="checkout-wallet-panel {{ ($walletControlsEnabled ?? false) ? '' : 'is-disabled' }}">
                                        <div class="checkout-wallet-panel-head">
                                            <div>
                                                <div class="checkout-address-label mb-1">Wallet Credit</div>
                                                <div class="checkout-wallet-copy">Apply wallet balance here before final order placement. If it covers everything, select Wallet to complete the order.</div>
                                            </div>
                                            <span class="checkout-wallet-balance">
                                                Balance: KSH.{{ number_format($walletBalance, 2) }}
                                            </span>
                                        </div>

                                        <div class="checkout-wallet-message" data-wallet-feedback></div>

                                        @if ($walletBalance > 0)
                                            <div class="checkout-wallet-copy mb-3">
                                                Available to apply now:
                                                <strong>KSH.{{ number_format($walletAvailableToApply, 2) }}</strong>
                                                @if ($walletApplied > 0)
                                                    <br>
                                                    Applied now:
                                                    <strong>KSH.{{ number_format($walletApplied, 2) }}</strong>
                                                    <br>
                                                    Remaining after wallet:
                                                    <strong>KSH.{{ number_format($grandTotal, 2) }}</strong>
                                                @endif
                                            </div>

                                            @if ($walletControlsEnabled ?? false)
                                                <div class="checkout-wallet-form" data-checkout-wallet-form>
                                                    <div class="input-group">
                                                        <input
                                                            type="number"
                                                            min="0.01"
                                                            step="0.01"
                                                            class="form-control"
                                                            value="{{ number_format($walletAmountValue, 2, '.', '') }}"
                                                            data-wallet-amount-input
                                                            placeholder="Enter wallet amount to apply">
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-primary" data-checkout-wallet-apply>Apply</button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="checkout-wallet-actions">
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-secondary checkout-wallet-action"
                                                        data-checkout-wallet-full
                                                        data-amount="{{ number_format($walletAvailableToApply, 2, '.', '') }}">
                                                        Use Full Available Wallet
                                                    </button>
                                                    @if ($walletApplied > 0)
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-danger checkout-wallet-action"
                                                            data-checkout-wallet-remove>
                                                            Remove Wallet Credit
                                                        </button>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="checkout-wallet-copy">
                                                    Choose a complete, serviceable delivery address first so wallet credit can be matched to the final checkout total.
                                                </div>
                                            @endif
                                        @else
                                            <div class="checkout-wallet-copy">
                                                You do not have any active wallet balance available right now.
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @error('payment_method')
                        <div class="text-danger mt-3">{{ $message }}</div>
                    @enderror
                </div>

                <div class="checkout-placeholder-card">
                    Payment choice and wallet credit now stay together here. Apply wallet balance if needed, then place the order with the remaining settlement method.
                </div>

                <button type="submit" class="btn btn-primary btn-block checkout-submit-btn" {{ $canProceed ? '' : 'disabled aria-disabled=true' }}>
                    Place Order
                </button>
            </form>

            @if (! $canProceed)
                <div class="checkout-address-meta mt-3">
                    Order placement is enabled only after a serviceable address with recipient details and pincode is selected.
                </div>
            @endif
        @else
            <div class="checkout-address-empty">
                Your cart is empty. Add products to the cart first, then come back here to confirm shipping and payment totals.
            </div>
        @endif
    </div>
</div>
