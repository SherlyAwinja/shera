(function ($) {
    "use strict";

    // Dropdown on mouse hover
    $(document).ready(function () {
        function toggleNavbarMethod() {
            if ($(window).width() > 992) {
                $('.navbar .dropdown').on('mouseover', function () {
                    $('.dropdown-toggle', this).trigger('click');
                }).on('mouseout', function () {
                    $('.dropdown-toggle', this).trigger('click').blur();
                });
            } else {
                $('.navbar .dropdown').off('mouseover').off('mouseout');
            }
        }
        toggleNavbarMethod();
        $(window).resize(toggleNavbarMethod);
    });


    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });


    // Vendor carousel
    $('.vendor-carousel').owlCarousel({
        loop: true,
        margin: 29,
        nav: false,
        autoplay: true,
        smartSpeed: 1000,
        responsive: {
            0:{
                items:2
            },
            576:{
                items:3
            },
            768:{
                items:4
            },
            992:{
                items:5
            },
            1200:{
                items:6
            }
        }
    });


    // Related carousel
    $('.related-carousel').owlCarousel({
        loop: true,
        margin: 29,
        nav: false,
        autoplay: true,
        smartSpeed: 1000,
        responsive: {
            0:{
                items:1
            },
            576:{
                items:2
            },
            768:{
                items:3
            },
            992:{
                items:4
            }
        }
    });

    // Categories carousel (single row slider)
    var categoriesCount = $('.categories-carousel .category-slide-item').length;
    $('.categories-carousel').owlCarousel({
        loop: categoriesCount > 4,
        margin: 20,
        nav: false,
        dots: true,
        autoplay: true,
        smartSpeed: 900,
        autoplayHoverPause: true,
        responsive: {
            0: {
                items: 1
            },
            576: {
                items: 2
            },
            992: {
                items: 3
            },
            1200: {
                items: 4
            }
        }
    });

    // New arrivals carousel (single row slider)
    var newArrivalsCount = $('.new-arrivals-carousel .new-arrival-item').length;
    $('.new-arrivals-carousel').owlCarousel({
        loop: newArrivalsCount > 4,
        margin: 20,
        nav: false,
        dots: true,
        autoplay: true,
        smartSpeed: 900,
        autoplayHoverPause: true,
        responsive: {
            0: {
                items: 1
            },
            576: {
                items: 2
            },
            992: {
                items: 3
            },
            1200: {
                items: 4
            }
        }
    });


    // Product Quantity
    $('.quantity button').not('.updateCartQty').on('click', function (e) {
        e.preventDefault(); // Prevent form submission
        var button = $(this);
        var input = button.closest('.quantity').find('input');
        var oldValue = parseInt(input.val()) || 0;
        var newVal = oldValue;
        if (button.hasClass('btn-plus')) {
            newVal = oldValue + 1;
        } else if (oldValue > 1) {
            newVal = oldValue - 1;
        }

        input.val(newVal);
    });

     $('#add-address-btn').on('click', function (e) {
        const form = $('#add-address-form');

        // Only if it's not already shown
        if (!form.hasClass('show')) {
            // Wait for Bootstrap collapse animation
            setTimeout(function () {
                $('html, body').animate({
                    scrollTop: form.offset().top - 100
                }, 500);
            }, 350); // Match Bootstrap's collapse transition (default 350ms)
        }
    });

})(jQuery);
