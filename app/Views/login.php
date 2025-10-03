<!doctype html>
<html lang="hu">
  <!--begin::Head-->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" >
    <title><?= htmlspecialchars($title ?? 'Bejelentkezés') ?></title>

    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" >
    <meta name="color-scheme" content="light dark" >
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" >
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" >
    <!--end::Accessibility Meta Tags-->

    <!--begin::Primary Meta Tags-->
    <meta name="title" content="Unitárius | Login Page" >
    <meta name="author" content="Unitarius" >
    <meta name="description" content="Login page for the Unitárius adatbazis." >
    <meta name="keywords" content="unitarius, admin, login" >
    <!--end::Primary Meta Tags-->

    <!-- Favicon (dynamic) -->
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" >

    <!--begin::Required Plugin(AdminLTE CSS, dynamic local)-->
    <link rel="stylesheet" href="<?= base_url('public/assets/adminlte/css/adminlte.css') ?>" >
    <!--end::Required Plugin(AdminLTE CSS)-->
    

    <!--begin::Fonts (CDN)-->
    <link rel="stylesheet"    href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
      integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
      crossorigin="anonymous"
      media="print"
      onload="this.media='all'"
    >
    <!--end::Fonts-->
    
    <!--begin::Third Party Plugin(Bootstrap Icons, CDN)-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous"  >
    <!--end::Third Party Plugin(Bootstrap Icons, CDN)-->

    
  </head>
  <!--end::Head-->

  <!--begin::Body-->
  <body class="login-page bg-body-secondary">

  <div class="login-box">
    <div class="login-logo">
    <!-- Change target as needed; keep dynamic base_url if you want to link home -->
    <a href="<?= base_url('/') ?>"><b>Unitárius</b> Adatbázis</a>
    </div>
    <!-- /.login-logo -->

    <div class="card">
        <div class="card-body login-card-body">
        <p class="login-box-msg">Jelentkezz be a folytatáshoz</p>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Use POST to /login; add name attributes -->
          <form action="<?= base_url('login') ?>" method="post" autocomplete="on" class="needs-validation" novalidate>
            <div class="input-group mb-3">
              <input name="email" type="email" class="form-control" placeholder="E-mail" required autofocus >
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="bi bi-envelope"></span>
                </div>
              </div>
            </div>
            <div class="input-group mb-3">
              <input name="password" type="password" class="form-control" placeholder="Jelszó" required >
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="bi bi-lock-fill"></span>
                </div>
              </div>
            </div>

        <!--begin::Row-->
            <div class="row">
            <div class="col-8">
                <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="rememberMe" name="remember" >
                <label class="form-check-label" for="rememberMe"> Emlékezz rám </label>
                </div>
            </div>
            <!-- /.col -->
            <div class="col-4">
                <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Belépés</button>
                </div>
            </div>
            <!-- /.col -->
            </div>
            <!--end::Row-->
        </form>   
            <div class="social-auth-links text-center mb-3 d-grid gap-2">
            <p>- VAGY -</p>
            <a href="<?= base_url('auth/google') ?>" class="btn btn-danger">
              <i class="bi bi-google me-2"></i> Sign in using Google
            </a>
        </div>
        <!-- /.social-auth-links -->
          <p class="mb-1">
            <a href="forgot-password.html">I forgot my password</a>
          </p>
        </div>
        <!-- /.login-card-body -->
      </div>
    </div>
    <!-- /.login-box -->

    <!--begin::Required Plugin(AdminLTE, dynamic local)-->
    <script src="<?= base_url('public/assets/adminlte/js/adminlte.min.js') ?>"></script>
    <!--end::Required Plugin(AdminLTE)-->

  </body>
  <!--end::Body-->
</html>
