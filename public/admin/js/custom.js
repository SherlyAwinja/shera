$(document).ready(function() {

    // Add/Remove Attribute Script
    const maxField   = 10;
    const wrapper    = $('.field_wrapper');
    const removeTmpl = '<a href="javascript:void(0);" class="btn btn-sm btn-danger remove_button" title="Remove row"><i class="fas fa-minus"></i></a>';
    const colorStockFields = $('#color-stock-fields');

    function colorStockInputId(color) {
        return 'color_stock_' + String(color || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function getSelectedProductColors() {
        const values = $('#product_color').val();

        return Array.isArray(values) ? values : [];
    }

    function collectCurrentColorStockValues() {
        const values = {};

        colorStockFields.find('input[name^="color_stock["]').each(function() {
            const input = $(this);
            const color = input.closest('.color-stock-row').data('color');

            if (typeof color !== 'undefined') {
                values[String(color)] = input.val();
            }
        });

        return values;
    }

    function renderColorStockFields(stockValues) {
        if (!colorStockFields.length) {
            return;
        }

        const selectedColors = getSelectedProductColors();
        const emptyText = colorStockFields.data('empty-text') || 'Select product colors to set stock quantities.';

        colorStockFields.empty();

        if (!selectedColors.length) {
            colorStockFields.append(
                $('<p>', {
                    class: 'text-muted mb-0',
                    id: 'color-stock-empty-state',
                    text: emptyText
                })
            );

            return;
        }

        $.each(selectedColors, function(_, color) {
            const normalizedColor = String(color);
            const inputId = colorStockInputId(normalizedColor);
            const currentValue = typeof stockValues[normalizedColor] !== 'undefined'
                ? stockValues[normalizedColor]
                : 0;
            const row = $('<div>', {
                class: 'd-flex align-items-center gap-2 mb-2 color-stock-row',
                'data-color': normalizedColor
            });

            row.append(
                $('<label>', {
                    class: 'mb-0 flex-grow-1 font-weight-medium',
                    for: inputId,
                    text: normalizedColor
                })
            );

            row.append(
                $('<input>', {
                    type: 'number',
                    min: 0,
                    step: 1,
                    class: 'form-control',
                    id: inputId,
                    name: 'color_stock[' + normalizedColor + ']',
                    value: currentValue,
                    style: 'max-width: 170px;'
                })
            );

            colorStockFields.append(row);
        });
    }

    if (colorStockFields.length) {
        let initialColorStocks = colorStockFields.data('initial-stocks') || {};

        if (typeof initialColorStocks === 'string') {
            try {
                initialColorStocks = JSON.parse(initialColorStocks);
            } catch (error) {
                initialColorStocks = {};
            }
        }

        renderColorStockFields(initialColorStocks);

        $('#product_color').on('change', function() {
            renderColorStockFields(collectCurrentColorStockValues());
        });
    }

    // Add new attribute row
    $(document).on('click', '.add_button', function(e) {
        e.preventDefault();
        if (wrapper.find('.attribute-row').length >= maxField) return;
        const row = $(this).closest('.attribute-row').clone();
        row.find('input').val('');  // clear values
        // Replace the + button in the cloned row with a - button
        row.find('.add_button').replaceWith(removeTmpl);
        wrapper.append(row);
    });

    wrapper.on('click', '.remove_button', function(e) {
        e.preventDefault();
        $(this).closest('.attribute-row').remove();
    });

    // Check Admin Password is correct or not
    $('#current_password').keyup(function() {
        var current_password = $('#current_password').val();
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'POST',
            url: '/admin/verify-password',
            data: {
                current_password: current_password
            },
            success: function(response) {
                if (response== false) {
                    $('#verifyPassword').html('<span class="text-danger small">Current password is incorrect</span>');
                } else {
                    $('#verifyPassword').html('<span class="text-success small">Current password is correct</span>');
                }
            },

            error: function() {
                alert.html('<font color="red">Something went wrong</font>');
            }
        });
    });

    // Delete Admin Profile Image
    $(document).on('click', '#deleteProfileImage', function() {
        if(confirm('Are you sure you want to delete your profile image?')) {
            var admin_id = $(this).data('admin-id');
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                type: 'POST',
                url: 'delete-profile-image',
                data: { admin_id: admin_id },
                success: function(response) {
                    if(response == true) {
                        alert(response['message']);
                        $('#profileImageBlock').remove();
                    }
                },
                error: function() {
                    alert('Something went wrong');
                }
            });
        }
    });

    // Update Subadmin Status
    $(document).on('click', '.updateSubadminStatus', function() {
        var status = $(this).children('i').data('status');
        var subadmin_id = $(this).data('subadmin_id');
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'POST',
            url: '/admin/update-subadmin-status',
            data: { status: status, subadmin_id: subadmin_id },
            success: function(response) {
                if(response['status'] == 0) {
                    $("a[data-subadmin_id='" + subadmin_id + "']").html('<i class="fas fa-toggle-off" style="color:gray" data-status="Inactive"></i>');
                } else if(response['status'] == 1) {
                    $("a[data-subadmin_id='" + subadmin_id + "']").html('<i class="fas fa-toggle-on" style="color:#3f6ed3" data-status="Active"></i>');
                }
            },
            error: function() {
                alert.html('<font color="red">Something went wrong</font>');
            }
        });
    });

    // Update Category Status
    $(document).on('click', '.updateCategoryStatus', function() {
        var status = $(this).find('i').data('status');
        var category_id = $(this).data('category_id');
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'POST',
            url: '/admin/update-category-status',
            data: { status: status, category_id: category_id },
            success: function(response) {
                if(response['status'] == 0) {
                    $("a[data-category_id='" + category_id + "']").html('<i class="fas fa-toggle-off" style="color:gray" data-status="Inactive"></i>');
                } else if(response['status'] == 1) {
                    $("a[data-category_id='" + category_id + "']").html('<i class="fas fa-toggle-on" style="color:#3f6ed3" data-status="Active"></i>');
                }
            },
            error: function() {
                alert.html('<font color="red">Something went wrong</font>');
            }
        });
    });

    //Update Banner Status
    $(document).on('click', '.updateBannerStatus', function() {
        var status = $(this).find('i').data('status');
        var banner_id = $(this).data('banner_id');
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                type: 'POST',
                url: '/admin/update-banner-status',
                data: { status: status, banner_id: banner_id },
                success: function(response) {
                    var icon=(response['status'] == 1)
                    ?'<i class="fas fa-toggle-on" style="color:#3f6ed3" data-status="Active"></i>'
                    :'<i class="fas fa-toggle-off" style="color:gray" data-status="Inactive"></i>';
                    $("a[data-banner_id='" + banner_id + "']").html(icon);
                },
                error: function() {
                    alert("<font color='red'>Something went wrong</font>");
                }
        });
    });

    // Update Product Status
    $(document).on('click', '.updateProductStatus', function() {
        var status = $(this).find('i').data('status');
        var product_id = $(this).data('product_id');
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'POST',
            url: '/admin/update-product-status',
            data: { status: status, product_id: product_id },
            success: function(response) {
                var $toggle = $("a.updateProductStatus[data-product_id='" + product_id + "']");
                var $statusBadge = $toggle.closest('tr').find('.product-status-badge');
                if(response['status'] == 0) {
                    $toggle.html('<i class="fas fa-toggle-off" style="color:gray" data-status="Inactive"></i>');
                    $toggle.attr('title', 'Enable Product');
                    $statusBadge.text('Inactive');
                } else if(response['status'] == 1) {
                    $toggle.html('<i class="fas fa-toggle-on" style="color:#3f6ed3" data-status="Active"></i>');
                    $toggle.attr('title', 'Disable Product');
                    $statusBadge.text('Active');
                }
            },
            error: function() {
                alert.html('<font color="red">Something went wrong</font>');
            }
        });
    });

    // Update Coupon Status
    $(document).on('click', '.updateCouponStatus', function() {
        var status = $(this).find('i').data('status');
        var coupon_id = $(this).data('coupon_id');
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'POST',
            url: '/admin/update-coupon-status',
            data: { status: status, coupon_id: coupon_id },
            success: function(response) {
                var $toggle = $("a.updateCouponStatus[data-coupon_id='" + coupon_id + "']");
                var $statusBadge = $toggle.closest('tr').find('.coupon-status-badge');
                if(response['status'] == 0) {
                    $toggle.html('<i class="fas fa-toggle-off" style="color:gray" data-status="Inactive"></i>');
                    $toggle.attr('title', 'Enable Coupon');
                    $statusBadge.text('Inactive');
                } else if(response['status'] == 1) {
                    $toggle.html('<i class="fas fa-toggle-on" style="color:#3f6ed3" data-status="Active"></i>');
                    $toggle.attr('title', 'Disable Coupon');
                    $statusBadge.text('Active');
                }
            },
            error: function() {
                window.alert('Something went wrong while updating the coupon status.');
            }
        });
    });

    // Update Review Status
    $(document).on('click', '.updateReviewStatus', function() {
        var status = $(this).find('i').data('status');
        var review_id = $(this).data('review-id');
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'POST',
            url: '/admin/update-review-status',
            data: { status: status, review_id: review_id },
            success: function(response) {
                var $toggle = $("a.updateReviewStatus[data-review-id='" + review_id + "']");
                if(response['status'] == 0) {
                    $toggle.html('<i class="fas fa-toggle-off" style="color:gray" data-status="Inactive"></i>');
                } else if(response['status'] == 1) {
                    $toggle.html('<i class="fas fa-toggle-on" style="color:#3f6ed3" data-status="Active"></i>');
                }
            },
            error: function() {
                alert('Something went wrong');
            }
        });
    });

    // Update Wallet Status
    $(document).on('click', '.updateWalletStatus', function() {
        var walletId = $(this).data('wallet-id');

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'POST',
            url: '/admin/update-wallet-status',
            data: { wallet_id: walletId },
            success: function(response) {
                var $toggle = $("a.updateWalletStatus[data-wallet-id='" + walletId + "']");
                var $row = $("tr[data-wallet-id='" + walletId + "']");
                var isActive = parseInt(response.status, 10) === 1;
                var toggleHtml = isActive
                    ? '<i class="fas fa-toggle-on" style="color:#3f6ed3" data-status="Active"></i>'
                    : '<i class="fas fa-toggle-off" style="color:gray" data-status="Inactive"></i>';

                $toggle.html(toggleHtml);
                $toggle.attr('title', isActive ? 'Disable wallet entry' : 'Enable wallet entry');

                var $statusBadge = $row.find('.wallet-status-text');
                $statusBadge
                    .text(isActive ? 'Active' : 'Inactive')
                    .removeClass('text-bg-success text-bg-secondary')
                    .addClass(isActive ? 'text-bg-success' : 'text-bg-secondary');

                if (Array.isArray(response.rows)) {
                    response.rows.forEach(function(item) {
                        var $ledgerRow = $("tr[data-wallet-id='" + item.id + "']");
                        $ledgerRow.find('.wallet-running-balance')
                            .text(item.running_balance_formatted)
                            .attr('data-order', item.running_balance);
                    });
                }

                $("tr[data-user-id='" + response.user_id + "']").find('.wallet-live-balance')
                    .text(response.user_live_balance_formatted)
                    .attr('data-order', response.user_live_balance);

                $("#selectedUserLiveBalance[data-user-id='" + response.user_id + "']").text(response.user_live_balance_formatted);
            },
            error: function() {
                window.alert('Something went wrong while updating the wallet status.');
            }
        });
    });

    // Update Filter Status
    $(document).on("click", ".updateFilterStatus", function () {
        var status = $(this).find("i").data("status");
        var filter_id = $(this).data("filter-id");

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'POST',
            url: '/admin/update-filter-status',
            data: {
                status: status,
                filter_id: filter_id
            },
            success: function (resp) {
                if (resp.status == 0) {
                    $("a[data-filter-id='" + filter_id + "']").html(
                        "<i class='fas fa-toggle-off' style='color:grey' data-status='Inactive'></i>"
                    );
                } else {
                    $("a[data-filter-id='" + filter_id + "']").html(
                        "<i class='fas fa-toggle-on' style='color:#3f6ed3' data-status='Active'></i>"
                    );
                }
            },
            error: function () {
                alert("Error");
            }
        });
    });

    // Update Attribute Status
    $(document).on('click', '.updateAttributeStatus', function() {
        var status = $(this).find('i').data('status');
        var attribute_id = $(this).data('attribute_id');
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'POST',
            url: '/admin/update-attribute-status',
            data: { status: status, attribute_id: attribute_id },
            success: function(response) {
                if(response['status'] == 0) {
                    $("a[data-attribute_id='" + attribute_id + "']").html('<i class="fas fa-toggle-off" style="color:gray" data-status="Inactive"></i>');
                } else if(response['status'] == 1) {
                    $("a[data-attribute_id='" + attribute_id + "']").html('<i class="fas fa-toggle-on" style="color:#3f6ed3" data-status="Active"></i>');
                }
            },
            error: function() {
                alert.html('<font color="red">Something went wrong</font>');
            }
        });
    });

    // Update Brand Status
    $(document).on('click', '.updateBrandStatus', function() {
        var status = $(this).find('i').data('status');
        var brand_id = $(this).data('brand_id');
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'POST',
            url: '/admin/update-brand-status',
            data: { status: status, brand_id: brand_id },
            success: function(response) {
                if(response['status'] == 0) {
                    $("a[data-brand_id='" + brand_id + "']").html('<i class="fas fa-toggle-off" style="color:gray" data-status="Inactive"></i>');
                } else if(response['status'] == 1) {
                    $("a[data-brand_id='" + brand_id + "']").html('<i class="fas fa-toggle-on" style="color:#3f6ed3" data-status="Active"></i>');
                }
            },
            error: function() {
                alert.html('<font color="red">Something went wrong</font>');
            }
        });
    });

    // Delete Category Image
    $(document).on('click', '#deleteCategoryImage', function() {
        if(confirm('Are you sure you want to delete this category image?')) {
            var category_id = $(this).data('category_id');
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                type: 'POST',
                url: '/admin/delete-category-image',
                data: { category_id: category_id },
                success: function(response) {
                    if(response['status'] == true) {
                        alert(response['message']);
                        $('#categoryImageBlock').remove();
                    }
                },
                error: function() {
                    alert.html('<font color="red">Something went wrong while deleting category image</font>');
                }
            });
        }
    });

    // Delete Size Chart Image
    $(document).on('click', '#deleteSizeChartImage', function() {
        if(confirm('Are you sure you want to delete this size chart image?')) {
            var category_id = $(this).data('category_id');
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                type: 'POST',
                url: '/admin/delete-sizechart-image',
                data: { category_id: category_id },
                success: function(response) {
                    if(response['status'] == true) {
                        alert(response['message']);
                        $('#sizeChartImageBlock').remove();
                    }
                },
                error: function() {
                    alert.html('<font color="red">Something went wrong while deleting size chart image</font>');
                }
            });
        }
    });

    // Confirm Delete
    /* $(".confirmDelete").click(function() {
        var name = $(this).attr('name');
        if(confirm('Are you sure you want to delete this ' + name + '?')) {
            return true;
        }
        return false;
    }); */

    $(document).on('click', '.confirmDelete', function(e) {
        e.preventDefault();

        let button = $(this);
        let module = button.data('module');
        let moduleid = button.data('id');
        let form = button.closest('form');
        let redirectUrl = "/admin/delete-" + module + "/" + moduleid;

        swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
        }).then((result) => {
            if(result.isConfirmed) {
                // Check if form exists AND delete route
                if(form.length > 0 && form.attr('action') && form.attr('method') === 'POST') {
                    // Create and append hidden_method input if not present
                    if (form.find('input[name="_method"]').length === 0) {
                        form.append('<input type="hidden" name="_method" value="DELETE">');
                    }
                    // Submit form
                    form.submit();
                } else {
                    // Redirect if no delete form exists
                    window.location.href = redirectUrl;
                }
            }
        });
    });

    // Initialize Select2
    $('#other_categories').select2({
        placeholder: "Select categories",
        width: '100%'
    });

    $('#product_color').select2({
        placeholder: "Select colors",
        width: '100%'
    });

    // Select All
    $('#selectAll').on('click', function() {
        let allValues = $('#other_categories option').map(function() {
            return $(this).val();
        }).get();
        $('#other_categories').val(allValues).trigger('change');
    });

    // Deselect All
    $('#deselectAll').on('click', function() {
        $('#other_categories').val(null).trigger('change');
    });

    // Select All Colors
    $('#colorSelectAll').on('click', function() {
        let allColorValues = $('#product_color option').map(function() {
            return $(this).val();
        }).get();
        $('#product_color').val(allColorValues).trigger('change');
    });

    // Deselect All Colors
    $('#colorDeselectAll').on('click', function() {
        $('#product_color').val(null).trigger('change');
    });

    // Initialize Select2 if available
    if ($.fn.select2) {
        $('#categoriesSelect').select2({ width: '100%' });
        $('#brandsSelect').select2({ width: '100%' });
        $('#usersSelect').select2({ width: '100%' });
        $('#walletUserSelect').select2({ width: '100%' });
        $('#walletFilterUser').select2({ width: '100%' });
        $('.select2-tags').select2({ width: '100%', tags: true, tokenSeparators: [','] });
    }

    function formatWalletCurrency(amount) {
        var parsedAmount = parseFloat(amount);
        if (Number.isNaN(parsedAmount)) {
            parsedAmount = 0;
        }

        return 'KES ' + parsedAmount.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function isWalletEntryEffective() {
        if (!$('#walletStatus').is(':checked')) {
            return false;
        }

        var expiryDate = $('#walletExpiryDate').val();
        if (!expiryDate) {
            return true;
        }

        var today = new Date().toISOString().split('T')[0];
        return expiryDate >= today;
    }

    function walletSignedAmount() {
        var amount = parseFloat($('#walletAmount').val());
        if (Number.isNaN(amount)) {
            amount = 0;
        }

        return $('input[name="action"]:checked').val() === 'debit' ? amount * -1 : amount;
    }

    function renderWalletBalancePreview() {
        if (!$('#walletBalancePanel').length) {
            return;
        }

        var baseBalance = parseFloat($('#walletBalancePanel').data('base-balance'));
        if (Number.isNaN(baseBalance)) {
            baseBalance = 0;
        }

        var projectedBalance = baseBalance + (isWalletEntryEffective() ? walletSignedAmount() : 0);
        $('#walletCurrentBalance').text(formatWalletCurrency(baseBalance));
        $('#walletProjectedBalance')
            .text(formatWalletCurrency(projectedBalance))
            .toggleClass('text-danger', projectedBalance < 0)
            .toggleClass('text-success', projectedBalance >= 0);

        if (projectedBalance < 0) {
            $('#walletBalanceWarning')
                .removeClass('d-none')
                .text('This entry would drive the user balance below zero.');
        } else {
            $('#walletBalanceWarning')
                .addClass('d-none')
                .text('');
        }
    }

    function loadWalletBalance() {
        if (!$('#walletUserSelect').length) {
            return;
        }

        var userId = $('#walletUserSelect').val();
        var walletId = $('#walletId').val();

        if (!userId) {
            $('#walletBalancePanel').data('base-balance', 0);
            $('#walletBalanceLabel').text('Current live balance');
            $('#walletBalanceHelp').text('Select a user to load live balance.');
            renderWalletBalancePreview();
            return;
        }

        $.ajax({
            type: 'GET',
            url: '/admin/wallets/live-balance',
            data: {
                user_id: userId,
                wallet_id: walletId
            },
            success: function(response) {
                $('#walletBalancePanel').data('base-balance', response.balance);
                $('#walletBalanceLabel').text(response.label);
                $('#walletBalanceHelp').text(response.help_text);
                renderWalletBalancePreview();
            },
            error: function() {
                $('#walletBalancePanel').data('base-balance', 0);
                $('#walletBalanceHelp').text('Unable to load the live wallet balance right now.');
                renderWalletBalancePreview();
            }
        });
    }

    // Coupon code generator
    function generateCouponCode(length = 8) {
        var chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        var result = "";
        for (var i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }

    // Toggle coupon input / regen button depending on option
    function updateCouponFieldVisibility() {
        var option = $('input[name="coupon_option"]:checked').val();
        if (option === 'Automatic') {
            $('#regenCoupon').show();
            if (!$('#coupon_code').val()) {
                $('#coupon_code').val(generateCouponCode());
            }
        } else {
            $('#regenCoupon').hide();
        }
    }

    // Initial run
    updateCouponFieldVisibility();

    // When coupon option radio changes
    $(document).on('change', 'input[name="coupon_option"]', function() {
        updateCouponFieldVisibility();
    });

    // Regenerate coupon code on click
    $(document).on('click', '#regenCoupon', function(e) {
        e.preventDefault();
        $('#coupon_code').val(generateCouponCode()).focus();
    });

    // Select all / Deselect all for multi-selects
    $(document).on('click', '.select-all', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        var $sel = $(target);
        if (!$sel.length) return;
        var vals = [];
        $sel.find('option').each(function() {
            vals.push($(this).val());
        });
        $sel.val(vals).trigger('change');
    });

    $(document).on('click', '.deselect-all', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        var $sel = $(target);
        if (!$sel.length) return;
        $sel.val([]).trigger('change');
    });

    if ($('#walletUserSelect').length) {
        loadWalletBalance();

        $(document).on('change', '#walletUserSelect', function() {
            loadWalletBalance();
        });

        $(document).on('change keyup', 'input[name="action"], #walletAmount, #walletExpiryDate, #walletStatus', function() {
            renderWalletBalancePreview();
        });
    }


});
