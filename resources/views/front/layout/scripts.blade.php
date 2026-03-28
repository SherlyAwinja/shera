<!-- Back to Top -->
<a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('front/lib/easing/easing.min.js') }}"></script>
<script src="{{ asset('front/lib/owlcarousel/owl.carousel.min.js') }}"></script>

<!-- Contact Javascript File -->
<script src="{{ asset('front/mail/jqBootstrapValidation.min.js') }}"></script>
<script src="{{ asset('front/mail/contact.js') }}"></script>

<!-- Template Javascript -->
<script src="{{ asset('front/js/main.js') }}"></script>

<script>
window.frontRoutes = {
    home: @json(route('home', [], false)),
    userLogin: @json(route('user.login', [], false)),
    userLoginPost: @json(route('user.login.post', [], false)),
    userRegister: @json(route('user.register', [], false)),
    userRegisterPost: @json(route('user.register.post', [], false)),
    userCheckout: @json(route('user.checkout.index', [], false)),
    cartStore: @json(route('cart.store', [], false)),
    cartRefresh: @json(route('cart.refresh', [], false)),
    cartBase: @json(route('cart.index', [], false)),
    cartWalletApply: @json(route('cart.apply.wallet', [], false)),
    cartWalletRemove: @json(route('cart.remove.wallet', [], false)),
    cartCheckoutPreview: @json(route('cart.checkout.preview', [], false)),
    cartCompleteWalletCheckout: @json(route('cart.complete-wallet-checkout', [], false)),
    cartCouponApply: @json(route('cart.apply.coupon', [], false)),
    cartCouponRemove: @json(route('cart.remove.coupon', [], false)),
    searchProducts: @json(route('search.products', [], false)),
    productPrice: @json(route('product.price', [], false)),
    productVariant: @json(route('product.variant', [], false)),
    forgotPost: @json(route('user.password.forgot.post', [], false)),
    resetPost: @json(route('user.password.reset.post', [], false)),
};
</script>

<!-- Custom Script -->
<script src="{{ asset('front/js/custom.js') }}"></script>

<!-- Filter Script -->
 <script src="{{ asset('front/js/filters.js') }}"></script>

 <!-- Image Zoom -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/elevatezoom/3.0.8/jquery.elevatezoom.min.js"></script>

<script>
$(document).ready(function () {

    if (typeof window.initProductZoom === 'function') {
        window.initProductZoom();
    }

    $('#product-carousel').on('slid.bs.carousel', function () {
        if (typeof window.initProductZoom === 'function') {
            window.initProductZoom();
        }
    });

});
</script>

<script>
    window.App = {
        csrfToken: "{{ csrf_token() }}",
    };
</script>
