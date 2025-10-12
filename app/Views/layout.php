<!doctype html>
<html lang="en">
  <!--begin::Head-->
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" >
  <title><?= htmlspecialchars($title ?? 'Kezdőlap') ?></title>

  <!--begin::Accessibility Meta Tags-->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" >
  <meta name="color-scheme" content="light dark" >
  <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" >
  <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" >
  <!--end::Accessibility Meta Tags-->
  <link rel="icon" href="<?= base_url('favicon.ico') ?>">

  <!--begin::AdminLTE CSS (includes Bootstrap 5)-->
  <meta name="supported-color-schemes" content="light dark" >
  <link rel="stylesheet" href="<?= base_url('public/assets/adminlte/css/adminlte.css') ?>" >
  <!--end::AdminLTE CSS-->

  <!--begin:: Custom css-->
  <link rel="stylesheet" href="<?= base_url('public/assets/css/custom.css') ?>">
  <!--end:: Constom css-->

  <!--begin::Fonts-->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q=" crossorigin="anonymous" media="print" onload="this.media='all'" >
  <!--end::Fonts-->

  <!--begin::Third Party Plugin(OverlayScrollbars CSS)-->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css" crossorigin="anonymous" >
  <!--end::Third Party Plugin(OverlayScrollbars CSS)-->

  <!-- Font Awesome 6 (CDN) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <!--begin::Third Party Plugin(Bootstrap Icons)-->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" >
  <!--end::Third Party Plugin(Bootstrap Icons)-->

</head>
<!--end::Head-->
<!--begin::Body-->
<body class="layout-fixed sidebar-expand-lg sidebar-mini bg-body-tertiary">
  <!--begin::App Wrapper-->
  <div class="app-wrapper">
  <!-- Show Navbar and Sidebar only when logged in -->
  <?php if (is_logged_in()): ?>
      <?php require dirname(__DIR__).'/Views/partials/navbar.php'; ?>
      <?php require dirname(__DIR__).'/Views/partials/sidebar.php'; ?>
  <?php endif; ?>

      <!--begin::App Main-->
      <!-- NOTE:
           We always render the <main> wrapper so inner views can stay lean.
           The $content gets injected inside .app-content > .container-fluid.
      -->
      <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
          <div class="container-fluid">
              <?php
              // Breadcrumbs (dinamikus)
              // Csak bejelentkezve jelenítjük meg, hogy illeszkedjen a navigációhoz
              if (is_logged_in()) {
                  require dirname(__DIR__) . '/Views/partials/breadcrumbs.php';
              }
            ?>
          </div>
        </div>
        <!--end::App Content Header-->

        <!--begin::App Content-->
        <div class="app-content">
          <div class="container-fluid">
            <?php if (is_logged_in()) { require dirname(__DIR__).'/Views/partials/flash.php'; } ?>
            <?= $content ?? '' ?>
          </div>
        </div>
        <!--end::App Content-->
      </main>
      <!--end::App Main-->

       <!--begin::Footer-->
      <footer class="app-footer">
        <!--begin::To the end-->
        <div class="float-end d-none d-sm-inline"><i class="fa-solid fa-face-smile-wink"></i></div>
        <!--end::To the end-->
        <!--begin::Copyright-->
        <strong>
          Copyright &copy; 2014-2025&nbsp;
          <a href="https://adminlte.io" class="text-decoration-none">AdminLTE.io</a>.
        </strong>
        All rights reserved.
        <!--end::Copyright-->
      </footer>
      <!--end::Footer-->
      
    </div>
    <!--end::App Wrapper-->

    <!-- Third Party Plugin (OverlayScrollbars) -->
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>

    <!-- Required Plugins for Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>

    <!-- AdminLTE JS (local) -->
    <script src="<?= base_url('public/assets/adminlte/js/adminlte.min.js') ?>"></script>
    

    <!-- OverlayScrollbars init -->
    <script>
      const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
      const Default = { scrollbarTheme: 'os-theme-light', scrollbarAutoHide: 'leave', scrollbarClickScroll: true };
      document.addEventListener('DOMContentLoaded', function () {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
        const isMobile = window.innerWidth <= 992;
        if (sidebarWrapper &&
            OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined &&
            !isMobile) {
          OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
            scrollbars: { theme: Default.scrollbarTheme, autoHide: Default.scrollbarAutoHide, clickScroll: Default.scrollbarClickScroll }
          });
        }
      });
    </script>

    <!--end::OverlayScrollbars Configure-->
    <!--begin::Color Mode Toggler-->
    <script>
      // Color Mode Toggler
      (() => {
        'use strict';

        const storedTheme = localStorage.getItem('theme');

        const getPreferredTheme = () => {
          if (storedTheme) {
            return storedTheme;
          }

          return globalThis.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        };

        const setTheme = function (theme) {
          if (theme === 'auto' && globalThis.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
          } else {
            document.documentElement.setAttribute('data-bs-theme', theme);
          }
        };

        setTheme(getPreferredTheme());

        const showActiveTheme = (theme, focus = false) => {
          const themeSwitcher = document.querySelector('#bd-theme');

          if (!themeSwitcher) {
            return;
          }

          const themeSwitcherText = document.querySelector('#bd-theme-text');
          const activeThemeIcon = document.querySelector('.theme-icon-active i');
          const btnToActive = document.querySelector(`[data-bs-theme-value="${theme}"]`);
          const svgOfActiveBtn = btnToActive.querySelector('i').getAttribute('class');

          for (const element of document.querySelectorAll('[data-bs-theme-value]')) {
            element.classList.remove('active');
            element.setAttribute('aria-pressed', 'false');
          }

          btnToActive.classList.add('active');
          btnToActive.setAttribute('aria-pressed', 'true');
          activeThemeIcon.setAttribute('class', svgOfActiveBtn);
          const themeSwitcherLabel = `${themeSwitcherText.textContent} (${btnToActive.dataset.bsThemeValue})`;
          themeSwitcher.setAttribute('aria-label', themeSwitcherLabel);

          if (focus) {
            themeSwitcher.focus();
          }
        };

        globalThis.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
          if (storedTheme !== 'light' || storedTheme !== 'dark') {
            setTheme(getPreferredTheme());
          }
        });

        globalThis.addEventListener('DOMContentLoaded', () => {
          showActiveTheme(getPreferredTheme());

          for (const toggle of document.querySelectorAll('[data-bs-theme-value]')) {
            toggle.addEventListener('click', () => {
              const theme = toggle.getAttribute('data-bs-theme-value');
              localStorage.setItem('theme', theme);
              setTheme(theme);
              showActiveTheme(theme, true);
            });
          }
        });
      })();
    </script>

    <!--end::Color Mode Toggler-->

  </body>
  <!--end::Body-->
</html>
