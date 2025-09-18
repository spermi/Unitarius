<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title ?? 'Kezdőlap') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 (CDN) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Font Awesome 6 (CDN) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <!-- AdminLTE CSS (LOCAL, base_url) -->
  <link rel="stylesheet" href="<?= base_url('public/assets/adminlte/css/adminlte.min.css') ?>">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <?php require dirname(__DIR__) . '/Views/partials/navbar.php'; ?>
  <?php require dirname(__DIR__) . '/Views/partials/sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">
        <?= $content ?? '' ?>
      </div>
    </section>
  </div>

  <footer class="main-footer text-center">
    <strong>&copy; <?= date('Y') ?> Unitarius.</strong> Minden jog fenntartva.
  </footer>
</div>

<!-- jQuery (CDN) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap bundle (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE JS (LOCAL, base_url) -->
<script src="<?= base_url('public/assets/adminlte/js/adminlte.min.js') ?>"></script>
</body>
</html>
