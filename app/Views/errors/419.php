<?php
http_response_code(419);

$pageTitle   = $title ?? 'Page Expired';
$description = $message ?? 'Your session has expired or the CSRF token is invalid.';
$homeUrl = function_exists('base_url') ? base_url('/') : '/';
?>
<!doctype html>
<html lang="en" class="h-100">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <!-- Favicon (dynamic) -->
  <link rel="icon" href="<?= base_url('favicon.ico') ?>" >
  <link rel="stylesheet" href="<?= base_url('public/assets/adminlte/css/adminlte.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
</head>
<body class="hold-transition bg-body-tertiary">

  <!-- Centered wrapper -->
  <div class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="text-center px-3" style="max-width: 560px;">
      <div class="mb-2">
        <span class="display-3 fw-bold text-warning">419</span>
      </div>
      <h2 class="mb-3">
        <i class="fas fa-hourglass-end text-warning me-2"></i>
        <?= htmlspecialchars($pageTitle) ?>
      </h2>
      <p class="text-body-secondary mb-2">
        <?= htmlspecialchars($description) ?>
      </p>
      <p class="text-body-secondary mb-4">
        Please refresh the page and try again. If the problem persists, sign out and sign in again.
      </p>

      <div class="d-flex gap-2 justify-content-center flex-wrap">
        <a class="btn btn-primary" href="<?= htmlspecialchars($homeUrl) ?>">
          <i class="fas fa-house me-2"></i>
          Back to dashboard
        </a>
        <button class="btn btn-outline-secondary" onclick="location.reload()">
          <i class="fas fa-rotate-right me-2"></i>
          Refresh
        </button>
      </div>
    </div>
  </div>

</body>
</html>
