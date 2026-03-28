var frontRoutes = window.frontRoutes || {};
var cartStoreUrl = frontRoutes.cartStore || '/cart';
var cartRefreshUrl = frontRoutes.cartRefresh || '/cart/refresh';
var cartBaseUrl = (frontRoutes.cartBase || '/cart').replace(/\/$/, '');
var cartWalletApplyUrl = frontRoutes.cartWalletApply || (cartBaseUrl + '/apply-wallet');
var cartWalletRemoveUrl = frontRoutes.cartWalletRemove || (cartBaseUrl + '/remove-wallet');
var cartCheckoutPreviewUrl = frontRoutes.cartCheckoutPreview || (cartBaseUrl + '/checkout-preview');
var cartCompleteWalletCheckoutUrl = frontRoutes.cartCompleteWalletCheckout || (cartBaseUrl + '/complete-wallet-checkout');
var cartCouponApplyUrl = frontRoutes.cartCouponApply || (cartBaseUrl + '/apply-coupon');
var cartCouponRemoveUrl = frontRoutes.cartCouponRemove || (cartBaseUrl + '/remove-coupon');
var checkoutPageUrl = frontRoutes.userCheckout || '/user/checkout';
var searchProductsUrl = frontRoutes.searchProducts || '/search-products';
var productPriceUrl = frontRoutes.productPrice || '/get-product-price';
var productVariantUrl = frontRoutes.productVariant || '/get-product-variant';
var cartSyncKey = 'shera_cart_updated_at';
var searchRequest = null;
var searchDebounceTimer = null;

function cartItemUrl(cartId) {
    return cartBaseUrl + '/' + cartId;
}

function redirectToCheckoutPage() {
    window.location.href = checkoutPageUrl;
}

function updateCartCount(count) {
    if (typeof count === 'undefined') {
        return;
    }

    $('.cart-count, .totalCartItems').text(count);
}

function hideSearchResults() {
    $('#search_result').hide();
}

function clearSearchResults() {
    if (searchRequest && searchRequest.readyState !== 4) {
        searchRequest.abort();
    }

    $('#search_result').hide().empty();
}

function notifyCartChanged(count) {
    updateCartCount(count);

    try {
        window.localStorage.setItem(cartSyncKey, JSON.stringify({
            count: count,
            ts: Date.now()
        }));
    } catch (error) {
        // Ignore storage write failures.
    }
}

function formatCurrency(value) {
    return 'KSH.' + Number(value || 0).toLocaleString();
}

function setCartPanelMessage(selector, type, message) {
    var container = $(selector);

    if (!container.length) {
        return;
    }

    if (!message) {
        container.empty();
        return;
    }

    var className = type === 'success' ? 'alert alert-success' : 'alert alert-danger';
    container.html('<div class="' + className + '">' + message + '</div>');
}

function replaceCartFragments(resp) {
    if (!resp) {
        return;
    }

    if (resp.items_html !== undefined) {
        if ($('#appendCartItems').length) {
            $('#appendCartItems').html(resp.items_html);
        } else {
            $('#cart-items-body').html(resp.items_html);
        }
    }

    if (resp.summary_html !== undefined) {
        if ($('#appendCartSummary').length) {
            $('#appendCartSummary').html(resp.summary_html);
        } else {
            $('#cart-summary-container').html(resp.summary_html);
        }
    }

    if (resp.totalCartItems !== undefined) {
        notifyCartChanged(resp.totalCartItems);
    }
}

function clearInlineMessages(form) {
    if (!form || !form.length) {
        return;
    }

    form.find('.print-success-msg').hide().removeClass('alert alert-success').empty();
    form.find('.print-error-msg').hide().removeClass('alert alert-danger').empty();
}

function showInlineMessage(form, type, message) {
    if (!form || !form.length) {
        return;
    }

    clearInlineMessages(form);

    var selector = type === 'success' ? '.print-success-msg' : '.print-error-msg';
    var className = type === 'success' ? 'alert alert-success' : 'alert alert-danger';

    form.find(selector)
        .addClass(className)
        .html(message)
        .show();
}

function detailPageSuccessMessage(message) {
    return '' +
        '<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between">' +
            '<span>' + message + '</span>' +
            '<a href="' + cartBaseUrl + '" class="btn btn-sm btn-primary mt-2 mt-sm-0 ml-sm-3">View Cart</a>' +
        '</div>';
}

function renderPriceHtml(response) {
    if (response.percent > 0 && response.final_price < response.product_price) {
        return '' +
            '<span class="text-danger final-price">' + formatCurrency(response.final_price) + '</span> ' +
            '<del class="text-muted original-price">' + formatCurrency(response.product_price) + '</del>';
    }

    return '<span class="final-price">' + formatCurrency(response.product_price) + '</span>';
}

function getDetailForm() {
    return $('#addToCart');
}

function getSelectedSize(form) {
    return form.find('input[name="size"]:checked').val() || '';
}

function preloadImage(imageUrl) {
    if (!imageUrl) {
        return;
    }

    var image = new Image();
    image.src = imageUrl;
}

function preloadVariantImages() {
    $('.js-color-swatch').each(function () {
        preloadImage($(this).data('image'));
    });
}

function updateStockDisplay(form, response) {
    var badge = form.find('.variant-stock-badge');
    var text = form.find('.variant-stock-text');

    badge
        .removeClass('badge-success badge-danger')
        .addClass(response.in_stock ? 'badge-success' : 'badge-danger')
        .text(response.stock_label || (response.in_stock ? 'In stock' : 'Out of stock'));

    text.text(response.stock_message || '');
}

function updatePurchaseState(form, canPurchase, message) {
    form.find('.detail-add-to-cart, .detail-qty-input, .detail-qty-control').prop('disabled', !canPurchase);

    var statusMessage = form.find('#purchase-status-message');

    if (canPurchase) {
        statusMessage.addClass('d-none').text('');
        return;
    }

    statusMessage.removeClass('d-none').text(message || 'This variant is currently out of stock.');
}

function buildSizeOptionMarkup(option, productId) {
    var optionId = 'size-option-' + option.id;
    var checked = option.checked ? ' checked' : '';
    var disabled = option.in_stock ? '' : ' disabled';
    var suffix = option.in_stock ? '' : ' (Out of stock)';

    return '' +
        '<div class="custom-control custom-radio custom-control-inline">' +
            '<input type="radio" class="custom-control-input getPrice" id="' + optionId + '" name="size" value="' + option.size + '" data-product-id="' + productId + '"' + checked + disabled + '>' +
            '<label class="custom-control-label" for="' + optionId + '">' + option.size + suffix + '</label>' +
        '</div>';
}

function renderVariantSizes(form, response) {
    var sizeSection = form.find('#product-size-section');
    var sizeOptions = form.find('#product-size-options');
    var emptyState = form.find('#product-size-empty');
    var sizes = response.sizes || [];

    sizeOptions.empty();

    if (!sizes.length) {
        sizeSection.addClass('d-none');
        emptyState.removeClass('d-none');
        return;
    }

    $.each(sizes, function (_, option) {
        sizeOptions.append(buildSizeOptionMarkup(option, response.product_id));
    });

    sizeSection.removeClass('d-none');
    emptyState.addClass('d-none');
}

window.initProductZoom = function () {
    if (typeof $.fn.elevateZoom !== 'function') {
        return;
    }

    $('.zoomContainer').remove();

    $('.zoom-image').each(function () {
        var image = $(this);

        if (image.data('elevateZoom')) {
            try {
                image.data('elevateZoom').destroy();
            } catch (error) {
                // Ignore cleanup issues from previous zoom instances.
            }

            image.removeData('elevateZoom');
            image.removeData('zoomImage');
        }
    });

    var activeImage = $('#product-carousel .carousel-item.active .zoom-image').first();

    if (!activeImage.length) {
        activeImage = $('#product-carousel .zoom-image').first();
    }

    if (!activeImage.length) {
        return;
    }

    activeImage.elevateZoom({
        zoomType: 'lens',
        lensShape: 'round',
        lensSize: 200
    });
};

function swapMainProductImage(imageUrl, altText) {
    if (!imageUrl) {
        return;
    }

    var carousel = $('#product-carousel');
    var mainImage = carousel.find('.carousel-item').first().find('.product-main-image');

    if (!mainImage.length) {
        return;
    }

    preloadImage(imageUrl);
    carousel.carousel(0);

    if (mainImage.attr('src') === imageUrl) {
        mainImage.attr('data-zoom-image', imageUrl);

        if (altText) {
            mainImage.attr('alt', altText);
        }

        if (typeof window.initProductZoom === 'function') {
            window.initProductZoom();
        }

        return;
    }

    mainImage.stop(true, true).fadeTo(150, 0.25, function () {
        mainImage
            .attr('src', imageUrl)
            .attr('data-zoom-image', imageUrl);

        if (altText) {
            mainImage.attr('alt', altText);
        }

        mainImage.off('load.variantImage').on('load.variantImage', function () {
            mainImage.fadeTo(180, 1);

            if (typeof window.initProductZoom === 'function') {
                window.initProductZoom();
            }
        });

        if (mainImage[0].complete) {
            mainImage.trigger('load.variantImage');
        }
    });
}

function syncActiveSwatch(clickedSwatch) {
    $('.js-color-swatch')
        .removeClass('active')
        .attr('aria-pressed', 'false');

    clickedSwatch
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function applyVariantResponse(form, response) {
    form.find('#selected-product-id').val(response.product_id);
    form.find('#selected-color-input').val(response.color || '');
    form.find('.getAttributePrice').html(renderPriceHtml(response));
    form.find('#selected-color-label').text(response.color || '');

    renderVariantSizes(form, response);
    updateStockDisplay(form, response);
    updatePurchaseState(form, !!response.can_purchase, response.stock_message);

    if (response.image) {
        var altText = response.color ? response.product_name + ' - ' + response.color : response.product_name;
        swapMainProductImage(response.image, altText);
    }
}

$(document).ready(function () {
    // Setup CSRF Token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    preloadVariantImages();

    var searchInput = $('#search_input');
    var searchResult = $('#search_result');

    function requestSearchResults(query) {
        if (!searchInput.length || !searchResult.length) {
            return;
        }

        if (searchRequest && searchRequest.readyState !== 4) {
            searchRequest.abort();
        }

        searchResult
            .html('<div class="px-3 py-2 text-muted small">Searching...</div>')
            .show();

        searchRequest = $.ajax({
            url: searchProductsUrl,
            method: 'GET',
            data: { q: query },
            success: function (response) {
                if ($.trim(searchInput.val()) !== query) {
                    return;
                }

                if ($.trim(response) === '') {
                    clearSearchResults();
                    return;
                }

                searchResult.html(response).show();
            },
            error: function (xhr, status) {
                if (status === 'abort') {
                    return;
                }

                searchResult
                    .html('<div class="px-3 py-2 text-danger small">Unable to load search results.</div>')
                    .show();
            },
            complete: function () {
                searchRequest = null;
            }
        });
    }

    if (searchInput.length && searchResult.length) {
        searchInput.on('input', function () {
            var query = $.trim($(this).val());

            window.clearTimeout(searchDebounceTimer);

            if (query.length < 3) {
                clearSearchResults();
                return;
            }

            searchDebounceTimer = window.setTimeout(function () {
                requestSearchResults(query);
            }, 250);
        });

        searchInput.on('focus', function () {
            if ($.trim($(this).val()).length >= 3 && $.trim(searchResult.html()) !== '') {
                searchResult.show();
            }
        });

        searchInput.on('keydown', function (event) {
            if (event.key === 'Escape') {
                hideSearchResults();
            }
        });

        $(document).on('click', function (event) {
            if ($(event.target).closest('.search-wrapper').length) {
                return;
            }

            hideSearchResults();
        });
    }

    // Add to Cart from Listing / Home / Search
    $(document).on('click', '.addToCartBtn', function(e) {
        e.preventDefault();
        var btn = $(this);
        var form = btn.closest('form');

        // Prevent multiple clicks
        if (btn.prop('disabled')) return;
        btn.prop('disabled', true);
        clearInlineMessages(form);

        var data;
        var url = cartStoreUrl;

        // Check if the button is inside a form (like on the product detail page)
        if (form.length > 0) {
            data = form.serialize();
            url = form.attr('action'); // Use the form's action URL (handles subfolders/routes correctly)
        } else {
            // Fallback for simple buttons without a form (quick add from listings)
            var product_id = btn.data('id');
            data = {
                product_id: product_id,
                qty: 1,
                size: btn.data('size') || "NA"
            };
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.status) {
                    if (form.length > 0) {
                        var successMessage = form.is('#addToCart')
                            ? detailPageSuccessMessage(response.message)
                            : response.message;

                        showInlineMessage(form, 'success', successMessage);
                    } else {
                        alert(response.message);
                    }

                    refreshCartView(response);
                } else {
                    if (form.length > 0) {
                        showInlineMessage(form, 'error', response.message);
                    } else {
                        alert(response.message);
                    }
                }
            },
            error: function (xhr) {
                var message = 'Error adding product to cart. Please try again.';

                if (xhr.status === 422 && xhr.responseJSON) {
                    message = xhr.responseJSON.message;
                }

                if (form.length > 0) {
                    showInlineMessage(form, 'error', message);
                } else {
                    alert(message);
                }
            },
            complete: function () {
                btn.prop('disabled', false);
            }
        });
    });

    $(document).on('change', '.getPrice', function () {
        var input = $(this);
        var productId = input.data('product-id');
        var size = input.val();
        var form = input.closest('form');

        $.ajax({
            url: productPriceUrl,
            method: 'POST',
            data: {
                product_id: productId,
                size: size
            },
            success: function (response) {
                if (response.status) {
                    form.find('.getAttributePrice').html(renderPriceHtml(response));
                    updateStockDisplay(form, response);
                    updatePurchaseState(form, !!response.in_stock, response.stock_message);
                } else {
                    updatePurchaseState(form, false, 'Unable to load the selected size.');
                    showInlineMessage(form, 'error', 'Unable to load the selected size price.');
                }
            },
            error: function (xhr) {
                var message = 'Unable to load the selected size price.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                updatePurchaseState(form, false, message);
                showInlineMessage(form, 'error', message);
            }
        });
    });

    $(document).on('click', '.js-color-swatch', function () {
        var swatch = $(this);
        var form = getDetailForm();

        if (!form.length || swatch.hasClass('active') || swatch.data('loading')) {
            return;
        }

        clearInlineMessages(form);
        swatch.data('loading', true);

        $.ajax({
            url: productVariantUrl,
            method: 'POST',
            data: {
                product_id: swatch.data('product-id') || form.find('#selected-product-id').val(),
                color: swatch.data('color'),
                size: getSelectedSize(form)
            },
            success: function (response) {
                if (!response.status) {
                    showInlineMessage(form, 'error', response.message || 'Unable to load the selected color variant.');
                    return;
                }

                syncActiveSwatch(swatch);
                applyVariantResponse(form, response);
            },
            error: function (xhr) {
                var message = 'Unable to load the selected color variant.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                showInlineMessage(form, 'error', message);
            },
            complete: function () {
                swatch.data('loading', false);
            }
        });
    });

    // Update Cart Quantity (Plus/Minus)
    $(document).on('click', '.updateCartQty', function () {
        var btn = $(this);
        var cartId = btn.data('cart-id');
        var direction = btn.data('dir'); // 'up' or 'down'
        var input = btn.closest('.quantity').find('.cart-qty');
        var currentQty = parseInt(input.val()) || 0;
        var newQty = currentQty;

        if (direction === 'up') {
            newQty = currentQty + 1;
        } else if (direction === 'down') {
            newQty = currentQty - 1;
        }

        // Prevent going below 1
        if (newQty < 1) {
            alert("Quantity must be at least 1");
            return;
        }

        // Update Input immediately for better UX
        input.val(newQty);

        $.ajax({
            url: cartItemUrl(cartId),
            method: 'POST',
            data: {
                _method: 'PATCH',
                qty: newQty
            },
            success: function (response) {
                if (response.status) {
                    refreshCartView();
                } else {
                    alert(response.message);
                    input.val(currentQty);
                }
            },
            error: function () {
                alert('Error updating quantity.');
                input.val(currentQty);
            }
        });
    });

    // Remove Cart Item
    $(document).on('click', '.removeCartItem', function () {
        var btn = $(this);
        var cartId = btn.data('cart-id');

        if (!confirm("Are you sure you want to remove this item?")) {
            return;
        }

        $.ajax({
            url: cartItemUrl(cartId),
            method: 'POST',
            data: {
                _method: 'DELETE'
            },
            success: function (response) {
                if (response.status) {
                    refreshCartView();
                } else {
                    alert(response.message);
                }
            },
            error: function () {
                alert('Error removing item.');
            }
        });
    });

    if ($('#appendCartItems').length > 0) {
        refreshCartView();
    }

    // Apply coupon
    $(document).on('submit', '#applyCouponForm', function (e) {
        e.preventDefault();

        var code = $('#coupon_code').val().trim();

        if (!code) {
            setCartPanelMessage('#coupon-msg', 'error', 'Please enter coupon code');
            return;
        }

        $.ajax({
            url: cartCouponApplyUrl,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                coupon_code: code
            },
            success: function (resp) {
                replaceCartFragments(resp);
                setCartPanelMessage('#coupon-msg', 'success', resp.message);
            },
            error: function (xhr) {
                if (xhr.responseJSON) {
                    const resp = xhr.responseJSON;

                    replaceCartFragments(resp);
                    setCartPanelMessage('#coupon-msg', 'error', resp.message || 'Error');
                } else {
                    setCartPanelMessage('#coupon-msg', 'error', 'Error applying coupon');
                }
            }
        });
    });

    $(document).on('submit', '#applyWalletForm', function (e) {
        e.preventDefault();

        var amount = parseFloat($('#wallet_amount').val());

        if (Number.isNaN(amount) || amount <= 0) {
            setCartPanelMessage('#wallet-msg', 'error', 'Enter a valid wallet amount.');
            return;
        }

        $.ajax({
            url: cartWalletApplyUrl,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                wallet_amount: amount
            },
            success: function (resp) {
                replaceCartFragments(resp);
                setCartPanelMessage('#wallet-msg', 'success', resp.message);
            },
            error: function (xhr) {
                if (xhr.status === 401 && frontRoutes.userLogin) {
                    window.location.href = frontRoutes.userLogin;
                    return;
                }

                if (xhr.responseJSON) {
                    replaceCartFragments(xhr.responseJSON);
                    setCartPanelMessage('#wallet-msg', 'error', xhr.responseJSON.message || 'Unable to apply wallet credit.');
                    return;
                }

                setCartPanelMessage('#wallet-msg', 'error', 'Unable to apply wallet credit.');
            }
        });
    });

    $(document).on('click', '#useFullWalletBtn', function (e) {
        e.preventDefault();
        var suggestedAmount = $(this).data('amount');

        if (suggestedAmount !== undefined) {
            $('#wallet_amount').val(suggestedAmount);
        }

        $('#applyWalletForm').trigger('submit');
    });

    $(document).on('click', '#removeWalletBtn', function (e) {
        e.preventDefault();

        $.ajax({
            url: cartWalletRemoveUrl,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (resp) {
                replaceCartFragments(resp);
                setCartPanelMessage('#wallet-msg', 'success', resp.message);
            },
            error: function (xhr) {
                if (xhr.responseJSON) {
                    replaceCartFragments(xhr.responseJSON);
                    setCartPanelMessage('#wallet-msg', 'error', xhr.responseJSON.message || 'Unable to remove wallet credit.');
                    return;
                }

                setCartPanelMessage('#wallet-msg', 'error', 'Unable to remove wallet credit.');
            }
        });
    });

    // Remove coupon
    $(document).on('click', '#removeCouponBtn', function (e) {
        e.preventDefault();

        $.ajax({
            url: cartCouponRemoveUrl,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (resp) {
                replaceCartFragments(resp);
                setCartPanelMessage('#coupon-msg', 'success', resp.message);
                $('#coupon_code').val('');
            },
            error: function (xhr) {
                if (xhr.responseJSON) {
                    replaceCartFragments(xhr.responseJSON);
                    setCartPanelMessage('#coupon-msg', 'error', xhr.responseJSON.message || 'Error removing coupon');
                    return;
                }

                setCartPanelMessage('#coupon-msg', 'error', 'Error removing coupon');
            }
        });
    });

    $(document).on('click', '.cart-checkout-preview-btn', function (e) {
        e.preventDefault();

        $.ajax({
            url: cartCheckoutPreviewUrl,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (resp) {
                replaceCartFragments(resp);
                setCartPanelMessage('#checkout-msg', 'success', resp.message);
                redirectToCheckoutPage();
            },
            error: function (xhr) {
                if (xhr.status === 401) {
                    redirectToCheckoutPage();
                    return;
                }

                if (xhr.responseJSON) {
                    replaceCartFragments(xhr.responseJSON);
                    setCartPanelMessage('#checkout-msg', 'error', xhr.responseJSON.message || 'Unable to validate checkout totals.');
                    return;
                }

                setCartPanelMessage('#checkout-msg', 'error', 'Unable to validate checkout totals.');
            }
        });
    });

    $(document).on('click', '.cart-wallet-checkout-btn', function (e) {
        e.preventDefault();

        if (!window.confirm('Complete this order fully with wallet credit?')) {
            return;
        }

        $.ajax({
            url: cartCompleteWalletCheckoutUrl,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (resp) {
                replaceCartFragments(resp);
                setCartPanelMessage('#checkout-msg', 'success', resp.message);
            },
            error: function (xhr) {
                if (xhr.status === 401 && frontRoutes.userLogin) {
                    window.location.href = frontRoutes.userLogin;
                    return;
                }

                if (xhr.responseJSON) {
                    replaceCartFragments(xhr.responseJSON);
                    setCartPanelMessage('#checkout-msg', 'error', xhr.responseJSON.message || 'Unable to complete wallet checkout.');
                    return;
                }

                setCartPanelMessage('#checkout-msg', 'error', 'Unable to complete wallet checkout.');
            }
        });
    });
    // Other existing cart handlers already call serve and replace fragments (update qty/remove)

});

/**
 * Refreshes the cart items and summary via AJAX
 */
function refreshCartView(initialResponse = null) {
    if (initialResponse && typeof initialResponse.totalCartItems !== 'undefined') {
        notifyCartChanged(initialResponse.totalCartItems);
    }

    if ($('#appendCartItems').length > 0) {
        $.ajax({
            url: cartRefreshUrl,
            method: 'GET',
            success: function(data) {
                replaceCartFragments(data);
            },
            error: function() {
                console.log('Error refreshing cart view');
            }
        });
    }
}

$(window).on('storage', function (event) {
    var originalEvent = event.originalEvent;

    if (!originalEvent || originalEvent.key !== cartSyncKey) {
        return;
    }

    if ($('#appendCartItems').length > 0) {
        refreshCartView();
        return;
    }

    if (!originalEvent.newValue) {
        return;
    }

    try {
        var payload = JSON.parse(originalEvent.newValue);
        updateCartCount(payload.count);
    } catch (error) {
        // Ignore malformed storage payloads.
    }
});

$(window).on('focus', function () {
    if ($('#appendCartItems').length > 0) {
        refreshCartView();
    }
});

$(document).on('visibilitychange', function () {
    if (document.visibilityState === 'visible' && $('#appendCartItems').length > 0) {
        refreshCartView();
    }
});

$(document).ready(function() {

    // ========================
    // LOGIN
    // ========================
    $(document).on('submit', '#loginForm', function(e) {
        e.preventDefault();

        var $form = $(this);

        // Clear previous errors
        $('.help-block.text-danger').text('');

        var $btn = $('#loginButton');
        $btn.prop('disabled', true).text('Please wait...');

        var payload = {
            email: $('#loginEmail').val(),
            password: $('#loginPassword').val(),
            user_type: $('input[name="user_type"]:checked').val()
        };

        $.ajax({
            url: $form.attr('action') || (window.frontRoutes && window.frontRoutes.userLoginPost ? window.frontRoutes.userLoginPost : 'user/login'),
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function(resp) {
                $btn.prop('disabled', false).text('Login');

                if (resp.success) {
                    $('#loginSuccess').html('<div class="alert alert-success">' + resp.message + '</div>');
                    window.location.href = resp.redirect || (window.frontRoutes && window.frontRoutes.home ? window.frontRoutes.home : './');
                } else {
                    $('#loginSuccess').html('<div class="alert alert-danger">Login failed</div>');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).text('Login');

                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(key, val) {
                        $('[data-error-for="' + key + '"]').text(val[0]);
                    });
                } else {
                    console.error(xhr.responseText || xhr);
                    $('#loginSuccess').html('<div class="alert alert-danger">Login failed</div>');
                }
            }
        });
    });

    // ========================
    // REGISTER
    // ========================
    $(document).on('submit', '#registerForm', function(e) {
        e.preventDefault();

        var $form = $(this);

        // Clear previous errors
        $('.help-block.text-danger').text('');

        var $btn = $('#registerButton');
        $btn.prop('disabled', true).text('Please wait...');

        var payload = {
            name: $('#name').val(),
            email: $('#email').val(),
            password: $('#password').val(),
            password_confirmation: $('#password_confirmation').val(),
            user_type: $('input[name="user_type"]:checked').val()
        };

        $.ajax({
            url: $form.attr('action') || (window.frontRoutes && window.frontRoutes.userRegisterPost ? window.frontRoutes.userRegisterPost : 'user/register'),
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function(resp) {
                $btn.prop('disabled', false).text('Register');

                if (resp.success) {
                    $('#registerSuccess').html('<div class="alert alert-success">' + resp.message + '</div>');
                    window.location.href = resp.redirect || (window.frontRoutes && window.frontRoutes.userLogin ? window.frontRoutes.userLogin : 'user/login');
                } else {
                    $('#registerSuccess').html('<div class="alert alert-danger">Registration failed</div>');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).text('Register');

                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(key, val) {
                        $('[data-error-for="' + key + '"]').text(val[0]);
                    });
                } else {
                    console.error(xhr.responseText || xhr);
                    $('#registerSuccess').html('<div class="alert alert-danger">Registration failed</div>');
                }
            }
        });
    });

});

(function () {
    // Wait for DOM
    document.addEventListener("DOMContentLoaded", function () {

        const starContainer = document.getElementById("star-rating");
        const ratingInput = document.getElementById("ratingInput");
        const reviewForm = document.getElementById("reviewForm");

        console.log("[review debug] init", {
            starContainer: !!starContainer,
            ratingInput: !!ratingInput,
            reviewForm: !!reviewForm
        });

        if (!starContainer || !ratingInput) {
            console.warn("[review debug] Missing starContainer or ratingInput. Aborting.");
            return;
        }

        // Defensive checks
        try {
            if (document.querySelectorAll("#star-rating").length !== 1) {
                console.warn("[review debug] #star-rating count:", document.querySelectorAll("#star-rating").length);
            }
            if (document.querySelectorAll("#ratingInput").length !== 1) {
                console.warn("[review debug] #ratingInput count:", document.querySelectorAll("#ratingInput").length);
            }
        } catch (e) {
            console.error(e);
        }

        // Update star visuals
        function setVisual(value) {
            const stars = starContainer.querySelectorAll("i[data-value]");
            stars.forEach(star => {
                const v = parseInt(star.getAttribute("data-value"), 10) || 0;

                if (v <= value) {
                    star.classList.remove("far");
                    star.classList.add("fas");
                } else {
                    star.classList.remove("fas");
                    star.classList.add("far");
                }
            });
        }

        // Initialize from existing value
        const initial = parseInt(ratingInput.value || "0", 10) || 0;
        if (initial) setVisual(initial);

        // Click event
        starContainer.addEventListener("click", function (evt) {
            const el = evt.target.closest("i[data-value]");
            if (!el) return;

            const val = parseInt(el.getAttribute("data-value"), 10) || 0;
            ratingInput.value = val;
            setVisual(val);

            console.log("[review debug] star clicked:", val);
        });

        // Hover effect
        starContainer.addEventListener("mouseover", function (evt) {
            const el = evt.target.closest("i[data-value]");
            if (!el) return;

            const val = parseInt(el.getAttribute("data-value"), 10) || 0;
            setVisual(val);
        });

        // Restore selected rating on mouseout
        starContainer.addEventListener("mouseout", function () {
            const current = parseInt(ratingInput.value || "0", 10) || 0;
            setVisual(current);
        });

        // AJAX Submit
        if (reviewForm) {
            reviewForm.addEventListener("submit", function (e) {
                e.preventDefault();

                if (!ratingInput.value || ratingInput.value == 0) {
                    alert("Please select a rating (1–5).");
                    return;
                }

                const tokenEl = reviewForm.querySelector('input[name="_token"]');
                const token = tokenEl
                    ? tokenEl.value
                    : document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

                const formData = new FormData(reviewForm);

                fetch(reviewForm.action, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": token,
                        "Accept": "application/json"
                    },
                    body: formData,
                    credentials: "same-origin"
                })
                .then(async res => {
                    const json = await res.json().catch(() => null);

                    const parent = reviewForm.parentElement;
                    const old = parent.querySelector(".ajax-review-alert");
                    if (old) old.remove();

                    const div = document.createElement("div");

                    if (res.ok) {
                        div.className = "ajax-review-alert alert alert-success mt-3";
                        div.innerHTML = json && json.message
                            ? json.message
                            : "Thank you! Review submitted.";

                        // Reset form
                        reviewForm.reset();
                        ratingInput.value = 0;
                        setVisual(0);

                    } else {
                        div.className = "ajax-review-alert alert alert-danger mt-3";

                        let msg = "Unable to submit review.";
                        if (json && json.message) {
                            msg = json.message;
                        } else if (json && json.errors) {
                            msg = Object.values(json.errors).flat().join("<br>");
                        }

                        div.innerHTML = msg;
                    }

                    parent.insertBefore(div, parent.firstChild);
                })
                .catch(err => {
                    console.error("[review debug] submit error:", err);
                    alert("Server error - try again later.");
                });
            });
        }

    });
})();

(function () {
    'use strict';

    function getCsrfToken() {
        if (window.App && window.App.csrfToken) {
            return window.App.csrfToken;
        }
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function showAlert(containerId, html) {
        const el = document.getElementById(containerId);
        if (el) el.innerHTML = html;
    }

    function clearFieldErrors() {
        document.querySelectorAll('[data-error-for]').forEach(el => {
            el.innerText = '';
        });
    }

    function displayFieldErrors(errors) {
        for (const key in errors) {
            const el = document.querySelector('[data-error-for="' + key + '"]');
            if (el) {
                el.innerText = errors[key][0];
            }
        }
    }

    function extractErrors(json) {
        if (json && json.errors && typeof json.errors === 'object') {
            return json.errors;
        }

        if (json && json.message && typeof json.message === 'object') {
            return json.message;
        }

        return {};
    }

    function extractErrorMessage(json) {
        const errors = extractErrors(json);
        const messages = Object.values(errors).flat().filter(Boolean);

        if (messages.length) {
            return messages.join('<br>');
        }

        if (json && typeof json.message === 'string') {
            return json.message;
        }

        return '';
    }

    async function handleFetch(fetchPromise, btn, originalText, successCallback, errorContainerId) {
        try {
            const res = await fetchPromise;

            btn.disabled = false;
            btn.innerText = originalText;

            if (res.ok) {
                const json = await res.json().catch(() => ({}));
                if (successCallback) successCallback(json);
                return;
            }

            if (res.status === 422) {
                const json = await res.json().catch(() => ({}));
                const errors = extractErrors(json);

                displayFieldErrors(errors);

                if (!Object.keys(errors).length && errorContainerId) {
                    const errorMessage = extractErrorMessage(json) || 'Unable to process your request.';
                    showAlert(errorContainerId, '<div class="alert alert-danger">' + errorMessage + '</div>');
                }

                return;
            }

            const text = await res.text().catch(() => '');
            console.error('Unexpected response:', res.status, text);

            if (errorContainerId) {
                showAlert(errorContainerId, '<div class="alert alert-danger">Unexpected server response. Please try again.</div>');
            }

        } catch (err) {
            btn.disabled = false;
            btn.innerText = originalText;
            console.error(err);

            if (errorContainerId) {
                showAlert(errorContainerId, '<div class="alert alert-danger">Unable to reach the server. Please try again.</div>');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {

        const csrfToken = getCsrfToken();

        // =========================
        // Forgot Password Form
        // =========================
        const forgotForm = document.getElementById('forgotForm');

        if (forgotForm) {
            const forgotBtn = document.getElementById('forgotButton');

            forgotForm.addEventListener('submit', function (e) {
                e.preventDefault();

                clearFieldErrors();
                showAlert('forgotSuccess', '');

                if (!forgotBtn) return;

                const originalText = forgotBtn.innerText;
                forgotBtn.disabled = true;
                forgotBtn.innerText = 'Please wait...';

                const email = forgotForm.email.value;

                const url = frontRoutes.forgotPost
                    ? frontRoutes.forgotPost
                    : '/user/password/forgot';

                const fetchPromise = fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ email: email })
                });

                handleFetch(fetchPromise, forgotBtn, originalText, function (json) {
                    showAlert(
                        'forgotSuccess',
                        '<div class="alert alert-success">' +
                        (json.message || 'Reset link sent.') +
                        '</div>'
                    );
                }, 'forgotSuccess');
            });
        }

        // =========================
        // Reset Password Form
        // =========================
        const resetForm = document.getElementById('resetForm');

        if (resetForm) {
            const resetBtn = document.getElementById('resetButton');

            resetForm.addEventListener('submit', function (e) {
                e.preventDefault();

                clearFieldErrors();
                showAlert('resetSuccess', '');

                if (!resetBtn) return;

                const originalText = resetBtn.innerText;
                resetBtn.disabled = true;
                resetBtn.innerText = 'Please wait...';

                const payload = {
                    token: resetForm.token ? resetForm.token.value : '',
                    email: resetForm.email ? resetForm.email.value : '',
                    password: resetForm.password ? resetForm.password.value : '',
                    password_confirmation: resetForm.password_confirmation
                        ? resetForm.password_confirmation.value
                        : ''
                };

                const url = frontRoutes.resetPost
                    ? frontRoutes.resetPost
                    : '/user/password/reset';

                const fetchPromise = fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                });

                handleFetch(fetchPromise, resetBtn, originalText, function (json) {
                    showAlert(
                        'resetSuccess',
                        '<div class="alert alert-success">' +
                        (json.message || 'Password reset successful.') +
                        '</div>'
                    );

                    if (json.redirect) {
                        setTimeout(() => {
                            window.location.href = json.redirect;
                        }, 1200);
                    }
                }, 'resetSuccess');
            });
        }

    });

})();
