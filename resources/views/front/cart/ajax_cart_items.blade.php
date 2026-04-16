@forelse($cartItems as $item)
@php
    $variantOptionsJson = json_encode(
        $item['variant_options'] ?? [],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
@endphp
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

                @if(!empty($item['has_variant_controls']))
                    <div
                        class="cart-variant-editor"
                        data-cart-id="{{ $item['cart_id'] }}"
                        data-selected-size="{{ $item['size'] }}"
                        data-selected-color="{{ $item['color'] ?? '' }}"
                        data-variant-options='{{ $variantOptionsJson }}'>
                        <div class="cart-variant-grid">
                            @if(!empty($item['can_edit_size']))
                                <label class="cart-variant-field">
                                    <span class="cart-variant-label">Size</span>
                                    <select class="form-control form-control-sm cart-variant-select" data-field="size">
                                        @foreach(($item['size_options'] ?? []) as $sizeOption)
                                            <option value="{{ $sizeOption }}" @selected((string) $sizeOption === (string) $item['size'])>
                                                {{ $sizeOption }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif

                            @if(!empty($item['can_edit_color']))
                                <label class="cart-variant-field">
                                    <span class="cart-variant-label">Color</span>
                                    <select class="form-control form-control-sm cart-variant-select" data-field="color">
                                        @foreach(($item['color_options'] ?? []) as $colorOption)
                                            <option value="{{ $colorOption }}" @selected((string) $colorOption === (string) ($item['color'] ?? ''))>
                                                {{ $colorOption }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif
                        </div>

                        <div class="cart-variant-note">
                            Choose size and color here. The line updates instantly and keeps your quantity.
                        </div>
                    </div>
                @endif
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
