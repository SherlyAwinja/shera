@forelse($cartItems as $item)
<tr>
    <td class="align-middle">
        <div class="cart-product-cell">
            <img src="{{ $item['image'] }}" alt="{{ $item['product_name'] }}" class="cart-product-thumb">
            <div>
                <a href="{{ filled($item['product_url']) ? url($item['product_url']) : 'javascript:void(0)' }}" class="cart-product-name">
                    {{ $item['product_name'] }}
                </a>
                <span class="cart-product-meta">
                    @php
                        $lineMeta = collect([
                            filled($item['size']) && strtoupper((string) $item['size']) !== 'NA' ? 'Size: ' . $item['size'] : null,
                            filled($item['color']) ? 'Color: ' . $item['color'] : null,
                        ])->filter()->implode(' | ');
                    @endphp

                    {{ $lineMeta ?: 'Ready for checkout' }}
                </span>
            </div>
        </div>
    </td>

    <td class="align-middle cart-price-cell">
        KES {{ number_format($item['unit_price'], 2) }}
    </td>

    <td class="align-middle">
        <div class="input-group quantity mx-auto cart-qty-wrap">
            <div class="input-group-btn">
                <button
                    class="btn btn-sm btn-primary btn-minus updateCartQty"
                    data-cart-id="{{ $item['cart_id'] }}"
                    data-dir="down">-</button>
            </div>

            <input
                type="text"
                class="form-control form-control-sm bg-secondary text-center cart-qty"
                data-cart-id="{{ $item['cart_id'] }}"
                value="{{ $item['qty'] }}">

            <div class="input-group-btn">
                <button
                    class="btn btn-sm btn-primary btn-plus updateCartQty"
                    data-cart-id="{{ $item['cart_id'] }}"
                    data-dir="up">+</button>
            </div>
        </div>
    </td>

    <td class="align-middle cart-total-cell">
        KES {{ number_format($item['line_total'], 2) }}
    </td>

    <td class="align-middle">
        <button
            class="btn btn-sm btn-primary removeCartItem cart-remove-btn"
            data-cart-id="{{ $item['cart_id'] }}">
            <i class="fa fa-times"></i>
        </button>
    </td>
</tr>
@empty
<tr>
    <td colspan="5" class="text-center">
        <div class="cart-empty-state">
            <i class="fa fa-shopping-bag"></i>
            <span>Your cart is empty.</span>
        </div>
    </td>
</tr>
@endforelse
