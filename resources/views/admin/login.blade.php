<!doctype html>
<html lang="en">
  <!--begin::Head-->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ config('app.name', 'Shera') }} | Admin Login</title>
    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
    <!--end::Accessibility Meta Tags-->
    <!--begin::Primary Meta Tags-->
    <meta name="title" content="{{ config('app.name', 'Shera') }} | Admin Login" />
    <meta name="author" content="{{ config('app.name', 'Shera') }}" />
    <meta
      name="description"
      content="Secure administrator sign-in for {{ config('app.name', 'Shera') }}."
    />
    <meta
      name="keywords"
      content="{{ config('app.name', 'Shera') }}, admin login, dashboard access, store admin"
    />
    <!--end::Primary Meta Tags-->
    <!--begin::Accessibility Features-->
    <!-- Skip links will be dynamically added by accessibility.js -->
    <meta name="supported-color-schemes" content="light dark" />
    <link rel="preload" href="{{ asset('admin/css/adminlte.css') }}" as="style" />
    <!--end::Accessibility Features-->
    <!--begin::Fonts-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
      integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
      crossorigin="anonymous"
      media="print"
      onload="this.media='all'"
    />
    <!--end::Fonts-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(OverlayScrollbars)-->
    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(Bootstrap Icons)-->
    <!--begin::Admin Theme Core-->
    <link rel="stylesheet" href="{{ asset('admin/css/adminlte.css') }}" />
    <!--end::Admin Theme Core-->
    <link rel="stylesheet" href="{{ asset('admin/css/admin-login.css') }}" />
  </head>
  <!--end::Head-->
  <!--begin::Body-->
  <body class="admin-login-page">
    <div class="admin-login-scene">
      <main class="admin-login-shell" role="main">
        <section class="admin-login-brand">
          <div class="admin-login-brand__top">
            <span class="admin-login-pill">
              <i class="bi bi-shield-lock"></i>
              <span>Admin portal</span>
            </span>

            <a href="{{ url('/') }}" class="admin-login-return">
              <i class="bi bi-arrow-left"></i>
              <span>Back to storefront</span>
            </a>
          </div>

          <h1 class="admin-login-heading">{{ config('app.name', 'Shera') }} command desk</h1>
          <p class="admin-login-copy">
            Oversee catalogue, customers, reviews, campaigns, and team access from one secure workspace.
          </p>

          <div class="admin-login-spotlight">
            <span class="admin-login-spotlight__label">Operational Focus</span>
            <strong>Start with the areas that move the store fastest.</strong>
            <p>
              Moderate customer feedback, adjust products, and refresh campaign assets without bouncing between tools.
            </p>
          </div>

          <div class="admin-login-highlights">
            <article class="admin-login-highlight">
              <span class="admin-login-highlight__icon">
                <i class="bi bi-box-seam"></i>
              </span>
              <h3>Catalogue control</h3>
              <p>Move quickly through products, brands, filters, and category structure.</p>
            </article>

            <article class="admin-login-highlight">
              <span class="admin-login-highlight__icon">
                <i class="bi bi-chat-square-heart"></i>
              </span>
              <h3>Review flow</h3>
              <p>Track customer sentiment and clear moderation queues without delay.</p>
            </article>

            <article class="admin-login-highlight admin-login-highlight--wide">
              <span class="admin-login-highlight__icon">
                <i class="bi bi-megaphone"></i>
              </span>
              <h3>Campaign updates</h3>
              <p>Keep homepage banners and customer-facing promotions aligned with current priorities.</p>
            </article>
          </div>
        </section>

        <section class="admin-login-panel">
          <div class="admin-login-panel__header">
            <span class="admin-login-kicker">
              <i class="bi bi-shield-check"></i>
              <span>Secure sign in</span>
            </span>
            <h2>Welcome back</h2>
            <p>Use your administrator credentials to continue to the control center.</p>
          </div>

          @if (session()->has('error_message') || $errors->any())
            <div class="admin-login-alert" role="alert">
              <div class="admin-login-alert__title">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Sign-in could not be completed</span>
              </div>

              @if (session()->has('error_message'))
                <p>{{ session()->get('error_message') }}</p>
              @endif

              @if ($errors->any())
                <ul @if (session()->has('error_message')) class="mt-2" @endif>
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              @endif
            </div>
          @endif

          <form action="{{ route('admin.login.request') }}" method="post" class="admin-login-form">
            @csrf

            <div class="admin-login-field">
              <label for="loginEmail">Email address</label>
              <div class="admin-login-input-wrap">
                <span class="admin-login-input-icon">
                  <i class="bi bi-envelope"></i>
                </span>
                <input
                  id="loginEmail"
                  name="email"
                  type="email"
                  class="form-control admin-login-input"
                  placeholder="name@example.com"
                  value="{{ old('email', $_COOKIE['email'] ?? '') }}"
                  autocomplete="username"
                  required
                />
              </div>
            </div>

            <div class="admin-login-field">
              <label for="loginPassword">Password</label>
              <div class="admin-login-input-wrap">
                <span class="admin-login-input-icon">
                  <i class="bi bi-lock-fill"></i>
                </span>
                <input
                  id="loginPassword"
                  name="password"
                  type="password"
                  class="form-control admin-login-input"
                  placeholder="Enter your password"
                  value="{{ $_COOKIE['password'] ?? '' }}"
                  autocomplete="current-password"
                  required
                />
                <button
                  type="button"
                  class="admin-login-password-toggle"
                  data-password-toggle
                  aria-controls="loginPassword"
                  aria-label="Show password"
                >
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="admin-login-actions">
              <div class="form-check admin-login-checkbox">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="remember"
                  name="remember"
                  @checked(old('remember', isset($_COOKIE['email'])))
                />
                <label class="form-check-label" for="remember">Remember me</label>
              </div>

              <span class="admin-login-helper">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Protected admin access</span>
              </span>
            </div>

            <button type="submit" class="btn admin-login-submit">
              <span>Sign in to dashboard</span>
              <i class="bi bi-arrow-right"></i>
            </button>
          </form>

          <p class="admin-login-footer">
            Need the storefront instead?
            <a href="{{ url('/') }}">Return to site</a>
          </p>
        </section>
      </main>
    </div>
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <script
      src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
    <script
      src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Required Plugin(Bootstrap 5)--><!--begin::Admin Theme Core-->
    <script src="{{ asset('admin/js/adminlte.js') }}"></script>
    <!--end::Admin Theme Core--><!--begin::OverlayScrollbars Configure-->
    <script>
      const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
      const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
      };
      document.addEventListener('DOMContentLoaded', function () {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
        if (sidebarWrapper && OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined) {
          OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
            scrollbars: {
              theme: Default.scrollbarTheme,
              autoHide: Default.scrollbarAutoHide,
              clickScroll: Default.scrollbarClickScroll,
            },
          });
        }
      });
    </script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-password-toggle]').forEach(function (toggle) {
          toggle.addEventListener('click', function () {
            const input = document.getElementById(toggle.getAttribute('aria-controls'));

            if (!input) {
              return;
            }

            const isPassword = input.getAttribute('type') === 'password';
            const icon = toggle.querySelector('i');

            input.setAttribute('type', isPassword ? 'text' : 'password');
            toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');

            if (icon) {
              icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
          });
        });
      });
    </script>
    <!--end::OverlayScrollbars Configure-->
    <!--end::Script-->
  </body>
  <!--end::Body-->
</html>
