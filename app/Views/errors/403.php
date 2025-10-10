<?php
http_response_code(403);

$pageTitle   = $title ?? 'Access Forbidden';
$description = isset($message) && is_string($message) && $message !== ''
    ? $message
    : 'You do not have permission to access this resource.';
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
        <span class="display-3 fw-bold text-danger">403</span>
      </div>
      <h2 class="mb-3">
        <i class="fas fa-ban text-danger me-2"></i>
        <?= htmlspecialchars($pageTitle) ?>
      </h2>
      <p class="text-body-secondary mb-2">
        <?= htmlspecialchars($description) ?>
      </p>
      <p class="text-body-secondary mb-4">
        If you believe you should have access, please contact your administrator.
      </p>

      <a class="btn btn-primary" href="<?= htmlspecialchars($homeUrl) ?>">
        <i class="fas fa-house me-2"></i>
        Back to dashboard
      </a>
    </div>
  </div>

</body>
</html>
