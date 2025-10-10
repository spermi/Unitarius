<?php
http_response_code(401);

$pageTitle   = $title ?? 'Unauthorized';
$description = isset($message) && is_string($message) && $message !== ''
    ? $message
    : 'You need to sign in to continue.';
$homeUrl  = function_exists('base_url') ? base_url('/') : '/';
$loginUrl = function_exists('base_url') ? base_url('login') : '/login';
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
        <span class="display-3 fw-bold text-info">401</span>
      </div>
      <h2 class="mb-3">
        <i class="fas fa-lock text-info me-2"></i>
        <?= htmlspecialchars($pageTitle) ?>
      </h2>
      <p class="text-body-secondary mb-2">
        <?= htmlspecialchars($description) ?>
      </p>
      <p class="text-body-secondary mb-4">
        Your session may have expired or you do not have valid credentials.
      </p>

      <div class="d-flex gap-2 justify-content-center flex-wrap">
        <a class="btn btn-primary" href="<?= htmlspecialchars($loginUrl) ?>">
          <i class="fas fa-right-to-bracket me-2"></i>
          Go to login
        </a>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($homeUrl) ?>">
          <i class="fas fa-house me-2"></i>
          Back to home
        </a>
      </div>
    </div>
  </div>

</body>
</html>
