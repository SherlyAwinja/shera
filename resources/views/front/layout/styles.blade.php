<!-- Google Web Fonts -->
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

<!-- Libraries Stylesheet -->
<link href="{{ asset('front/lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">

<!-- Customized Bootstrap Stylesheet -->
<link href="{{ asset('front/css/style.css') }}" rel="stylesheet">

<!-- for 3 level category dropdown -->
<style>
.dropdown-submenu
{
    position: relative;
}
.dropdown-submenu .dropdown-menu
{
    top: 0;
    left: 100%;
    margin-top: -1px;
    display: none;
}
.dropdown-submenu:hover .dropdown-menu
{
    display: block;
}
.dropdown-submenu > a:after
{
    display:block;
    content: " »";
    float: right;
    width: 0;
    height: 0;
    border-color: transparent;
    border-style: solid;
    border-width: 5px 0 5px 5px;
    border-left-color: #ccc;
    margin-top: 5px;
    margin-right: -10px;
}
</style>
