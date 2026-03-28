<div class="checkout-summary-card">
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

            <div class="checkout-placeholder-card">
                Quantity changes and coupon controls stay in the cart for now. This panel is ready to consume both when that step is connected.
            </div>

            <form method="POST" action="{{ route('user.checkout.placeOrder', [], false) }}">
                @csrf
                @if ($selectedAddressId)
                    <input type="hidden" name="address_id" value="{{ $selectedAddressId }}">
                @endif
                <button type="submit" class="btn btn-primary btn-block checkout-submit-btn" {{ $canProceed ? '' : 'disabled aria-disabled=true' }}>
                    Proceed to Payment
                </button>
            </form>

            @if (! $canProceed)
                <div class="checkout-address-meta mt-3">
                    Proceed to payment is enabled only after a serviceable address with recipient details and pincode is selected.
                </div>
            @endif
        @else
            <div class="checkout-address-empty">
                Your cart is empty. Add products to the cart first, then come back here to confirm shipping and payment totals.
            </div>
        @endif
    </div>
</div>
