<!-- Google Web Fonts -->
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

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

.user-nav-dropdown .dropdown-toggle
{
    max-width: 240px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-nav-menu
{
    min-width: 220px;
    margin-top: 0.35rem;
    padding: 0.4rem;
    border: 1px solid rgba(26, 26, 26, 0.08);
    border-radius: 14px;
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
}

.user-nav-menu .dropdown-item
{
    border-radius: 10px;
    padding: 0.65rem 0.85rem;
    font-weight: 500;
    white-space: normal;
}

.user-nav-menu .dropdown-item:hover,
.user-nav-menu .dropdown-item:focus,
.user-nav-menu .dropdown-item.active
{
    background: rgba(209, 156, 151, 0.16);
    color: #1c1c1c;
}

.user-nav-logout-form
{
    margin: 0;
}

.user-nav-logout
{
    width: 100%;
    border: 0;
    background: transparent;
    text-align: left;
}

@media (max-width: 991.98px)
{
    .user-nav-dropdown
    {
        width: 100%;
    }

    .user-nav-dropdown .dropdown-toggle
    {
        max-width: none;
        width: 100%;
        justify-content: space-between;
    }

    .user-nav-dropdown .dropdown-menu
    {
        position: static !important;
        float: none;
        width: 100%;
        margin-top: 0.2rem;
        box-shadow: none;
    }
}
</style>
