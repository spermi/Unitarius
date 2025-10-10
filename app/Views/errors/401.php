<?php
http_response_code(401);

$pageTitle = $title ?? 'Unauthorized';
$description = isset($message) && is_string($message) && $message !== ''
    ? $message
    : 'You need to sign in to continue.';
$homeUrl = function_exists('base_url') ? base_url('/') : '/';
$loginUrl = function_exists('base_url') ? base_url('login') : '/login';
$adminlteCss = function_exists('base_url')
    ? base_url('public/assets/adminlte/css/adminlte.css')
    : '/public/assets/adminlte/css/adminlte.css';
$customCss = function_exists('base_url')
    ? base_url('public/assets/css/custom.css')
    : '/public/assets/css/custom.css';
?>
<!doctype html>
<html lang="en" class="h-100">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= $adminlteCss ?>">
  <link rel="stylesheet" href="<?= $customCss ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-TN9/q9Z7cc5psxEsxwT1+HK6FlKZI/9Al1nY8W6N9cWcG8j0p3Z3tOshkDDQuBw99b+zV+jqw+Ur4sM0SdA1/A==" crossorigin="anonymous">
</head>
<body class="hold-transition error-page bg-body-tertiary">
  <div class="error-page text-center text-sm-start">
    <h1 class="headline text-info">401</h1>
    <div class="error-content">
      <h2 class="mb-3">
        <i class="fas fa-lock text-info me-2"></i>
        <?= htmlspecialchars($pageTitle) ?>
      </h2>
      <p class="text-body-secondary">
        <?= htmlspecialchars($description) ?>
      </p>
      <p class="text-body-secondary">
        Your session may have expired or you do not have valid credentials.
      </p>
      <div class="d-flex flex-column flex-sm-row gap-2 mt-3">
        <a class="btn btn-primary" href="<?= htmlspecialchars($loginUrl) ?>">
          <i class="fas fa-right-to-bracket me-2"></i>
          Go to login
        </a>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($homeUrl) ?>">
          <i class="fas fa-home me-2"></i>
          Back to home
        </a>
      </div>
    </div>
  </div>
</body>
</html>
