@php($appliedCoupon = session('applied_coupon'))

<div class="card-body cart-summary-body">
    <div class="d-flex justify-content-between mb-3 pt-1 cart-summary-row">
        <h6 class="font-weight-medium mb-0">Subtotal</h6>
        <h6 class="font-weight-medium mb-0">KSH.{{ number_format($subtotal ?? 0, 2) }}</h6>
    </div>
    @if(isset($discount) && $discount > 0)
    <div class="d-flex justify-content-between mb-3 cart-summary-row">
        <h6 class="font-weight-medium mb-0">Discount</h6>
        <h6 class="font-weight-medium text-success mb-0">-KSH.{{ number_format($discount, 2) }}</h6>
    </div>
    @endif
    <div class="d-flex justify-content-between mb-3 cart-summary-row">
        <h6 class="font-weight-medium mb-0">Payable Before Wallet</h6>
        <h6 class="font-weight-medium mb-0">KSH.{{ number_format($payable_before_wallet ?? 0, 2) }}</h6>
    </div>
    @if(isset($wallet_applied) && $wallet_applied > 0)
    <div class="d-flex justify-content-between mb-3 cart-summary-row">
        <h6 class="font-weight-medium mb-0">Wallet Applied</h6>
        <h6 class="font-weight-medium text-success mb-0">-KSH.{{ number_format($wallet_applied, 2) }}</h6>
    </div>
    @endif
    <div class="cart-summary-note">
        <i class="fa fa-check-circle"></i>
        <span>Taxes and delivery options can be added at checkout.</span>
    </div>

    <div class="border-top mt-4 pt-4">
        <h6 class="font-weight-medium mb-3">Coupon Code</h6>
        <div id="coupon-msg" class="mb-3"></div>

        @if($appliedCoupon)
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between">
                <span class="text-success font-weight-medium mb-2 mb-sm-0">
                    Applied: {{ $appliedCoupon }}
                </span>
                <button type="button" class="btn btn-outline-danger btn-sm" id="removeCouponBtn">
                    Remove
                </button>
            </div>
        @else
            <form id="applyCouponForm">
                <div class="input-group">
                    <input
                        type="text"
                        class="form-control"
                        id="coupon_code"
                        name="coupon_code"
                        placeholder="Enter coupon code">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </div>
                </div>
            </form>
        @endif
    </div>

</div>
<div class="card-footer border-secondary bg-transparent cart-summary-footer">
    <div class="d-flex justify-content-between mt-2 cart-summary-total">
        <h5 class="font-weight-bold mb-0">Remaining Payable</h5>
        <h5 class="font-weight-bold mb-0">KSH.{{ number_format($total ?? 0, 2) }}</h5>
    </div>

    <a href="{{ route('user.checkout.index', [], false) }}" class="btn btn-block btn-primary py-3 cart-proceed-checkout-btn">
        Proceed to Checkout
    </a>

    <p class="small text-muted mb-0">
        Wallet credit and payment method selection are now handled during checkout after the delivery address is confirmed.
    </p>
</div>
