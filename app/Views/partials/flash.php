<?php
// ---------------------------------------------------------
// Flash message partial (AdminLTE style)
// ---------------------------------------------------------
$flashes = flash_get();
if (!empty($flashes) && is_array($flashes)): ?>
<div class="app-content">
  <div class="container-fluid">
  <div class="my-3">
    <?php foreach ($flashes as $type => $msg): ?>
      <?php
        $alertClass = match ($type) {
          'success' => 'alert-success',
          'error', 'danger' => 'alert-danger',
          'warning' => 'alert-warning',
          'info' => 'alert-info',
          default => 'alert-secondary',
        };
        $icon = match ($type) {
          'success' => 'fa-circle-check',
          'error', 'danger' => 'fa-triangle-exclamation',
          'warning' => 'fa-circle-exclamation',
          'info' => 'fa-circle-info',
          default => 'fa-bell',
        };
      ?>
      <div class="alert <?= $alertClass ?> alert-dismissible fade show shadow-sm" role="alert">
        <i class="fa-solid <?= $icon ?> me-2"></i>
        <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endforeach; ?>
  </div>
  </div>
</div>  
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
      document.querySelectorAll('.alert').forEach(el => {
        const alert = bootstrap.Alert.getOrCreateInstance(el);
        alert.close();
      });
    }, 10000);
  });
  </script>
<?php endif; ?>
