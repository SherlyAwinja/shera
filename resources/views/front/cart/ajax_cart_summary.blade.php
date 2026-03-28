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

    <div class="border-top mt-4 pt-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h6 class="font-weight-medium mb-1">Wallet Credit</h6>
                <p class="text-muted small mb-0">Apply some or all of your live wallet balance before final checkout.</p>
            </div>
            @auth
                <span class="badge badge-light px-3 py-2">
                    Balance: KSH.{{ number_format($wallet_balance ?? 0, 2) }}
                </span>
            @endauth
        </div>

        <div id="wallet-msg" class="mb-3"></div>

        @auth
            @if(($wallet_balance ?? 0) > 0)
                <div class="small text-muted mb-3">
                    Available to apply now:
                    <strong>KSH.{{ number_format($wallet_available_to_apply ?? 0, 2) }}</strong>
                </div>

                @if(($wallet_applied ?? 0) > 0)
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border rounded px-3 py-2 mb-3">
                        <div>
                            <div class="font-weight-medium text-success">Wallet credit is active on this cart</div>
                            <div class="small text-muted">
                                Applied now: KSH.{{ number_format($wallet_applied ?? 0, 2) }}
                                @if(($remaining_payable ?? 0) > 0)
                                    | Remaining payable: KSH.{{ number_format($remaining_payable ?? 0, 2) }}
                                @else
                                    | Wallet fully covers this cart
                                @endif
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm mt-2 mt-sm-0" id="removeWalletBtn">
                            Remove
                        </button>
                    </div>
                @endif

                <form id="applyWalletForm">
                    <div class="input-group">
                        <input
                            type="number"
                            min="0.01"
                            step="0.01"
                            class="form-control"
                            id="wallet_amount"
                            name="wallet_amount"
                            value="{{ number_format(($wallet_applied ?? 0) > 0 ? ($wallet_applied ?? 0) : ($wallet_available_to_apply ?? 0), 2, '.', '') }}"
                            placeholder="Enter wallet amount to apply">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary">Apply</button>
                        </div>
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button"
                            class="btn btn-outline-secondary btn-sm mr-2 mb-2"
                            id="useFullWalletBtn"
                            data-amount="{{ number_format($wallet_available_to_apply ?? 0, 2, '.', '') }}">
                        Use Full Available Wallet
                    </button>
                    @if(($wallet_applied ?? 0) > 0)
                        <span class="small text-muted align-self-center mb-2">
                            Requested: KSH.{{ number_format($requested_wallet_amount ?? 0, 2) }}
                        </span>
                    @endif
                </div>
            @else
                <div class="small text-muted">
                    You do not have any active wallet credit available right now.
                </div>
            @endif
        @else
            <div class="small text-muted">
                <a href="{{ route('user.login', [], false) }}">Log in</a> to apply wallet credit to this cart.
            </div>
        @endauth
    </div>
</div>
<div class="card-footer border-secondary bg-transparent cart-summary-footer">
    <div id="checkout-msg" class="mb-3"></div>

    <div class="d-flex justify-content-between mt-2 cart-summary-total">
        <h5 class="font-weight-bold mb-0">Remaining Payable</h5>
        <h5 class="font-weight-bold mb-0">KSH.{{ number_format($total ?? 0, 2) }}</h5>
    </div>

    <button type="button" class="btn btn-block btn-outline-primary my-3 py-3 cart-checkout-preview-btn">
        Validate Wallet & Checkout Plan
    </button>

    <a href="{{ route('user.checkout.index', [], false) }}" class="btn btn-block btn-primary py-3 cart-proceed-checkout-btn">
        Proceed to Checkout
    </a>

    @if(($wallet_applied ?? 0) > 0 && ($can_checkout_with_wallet_only ?? false))
        <button type="button" class="btn btn-block btn-primary py-3 cart-wallet-checkout-btn">
            Complete Order With Wallet
        </button>
    @elseif(($wallet_applied ?? 0) > 0 && ($requires_payment_gateway ?? false))
        <p class="small text-muted mb-0">
            Wallet credit covers part of this cart. The remaining KSH.{{ number_format($remaining_payable ?? 0, 2) }} still needs your payment gateway flow.
        </p>
    @else
        <p class="small text-muted mb-0">
            Apply wallet credit to reduce the payable amount before continuing to your payment step.
        </p>
    @endif
</div>
