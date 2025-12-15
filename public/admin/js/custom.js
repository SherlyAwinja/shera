$(document).ready(function() {

    // Add/Remove Attribute Script
    const maxField   = 10;
    const wrapper    = $('.field_wrapper');
    const removeTmpl = '<a href="javascript:void(0);" class="btn btn-sm btn-danger remove_button" title="Remove row"><i class="fas fa-minus"></i></a>';

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
                    $('#verifyPassword').html('<font color="red">Current Password is incorrect</font>');
                } else {
                    $('#verifyPassword').html('<font color="green">Current Password is correct</font>');
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
                if(response['status'] == 0) {
                    $("a[data-product_id='" + product_id + "']").html('<i class="fas fa-toggle-off" style="color:gray" data-status="Inactive"></i>');
                } else if(response['status'] == 1) {
                    $("a[data-product_id='" + product_id + "']").html('<i class="fas fa-toggle-on" style="color:#3f6ed3" data-status="Active"></i>');
                }
            },
            error: function() {
                alert.html('<font color="red">Something went wrong</font>');
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
});