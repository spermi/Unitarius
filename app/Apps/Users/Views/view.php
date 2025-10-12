<?php
/** @var array<string,mixed> $user */
/** @var array<int,array<string,mixed>> $roles */
/** @var array<int,array<string,mixed>> $perms */

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$avatar = !empty($user['avatar'])
    ? e($user['avatar'])
    : base_url('public/assets/adminlte/img/user.png');

$statusBadge = $user['status'] == 1
    ? '<span class="badge bg-success">Aktív</span>'
    : '<span class="badge bg-secondary">Inaktív</span>';
?>

<!-- DataTables (CDN) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h3 class="mb-0">Felhasználó adatai</h3>
      </div>
      <div class="col-sm-6 text-end">
        <a href="<?= base_url('/users') ?>" class="btn btn-secondary btn-sm">
          <i class="fa-solid fa-arrow-left"></i> Vissza a listához
        </a>
        <?php if (function_exists('can') && can('users.manage')): ?>
          <a href="<?= base_url('/users/' . (int)$user['id'] . '/edit') ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-pen-to-square"></i> Szerkesztés
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">

    <div class="card card-outline card-primary mb-4">
      <div class="card-body d-flex align-items-center">
        <img src="<?= $avatar ?>" alt="avatar" class="rounded-circle me-4"
             width="96" height="96" referrerpolicy="no-referrer" style="object-fit:cover;">
        <div>
          <h4 class="mb-1"><?= e($user['name'] ?? '') ?></h4>
          <p class="mb-1 text-muted"><?= e($user['email'] ?? '') ?></p>
          <p class="mb-0">Státusz: <?= $statusBadge ?></p>
          <small class="text-secondary">
            Utolsó bejelentkezés: <?= e($user['last_login_at'] ?? '—') ?><br>
            Létrehozva: <?= e($user['created_at'] ?? '—') ?><br>
            Módosítva: <?= e($user['updated_at'] ?? '—') ?>
          </small>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- SZEREPEK -->
      <div class="col-md-6">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0"><i class="fa-solid fa-user-shield"></i> Szerepek</h3>
          </div>
          <div class="card-body">
            <?php if (empty($roles)): ?>
              <p class="text-secondary mb-0">Nincsenek hozzárendelt szerepek.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($roles as $r): ?>
                  <li class="list-group-item">
                    <i class="fa-regular fa-circle-check text-primary me-2"></i>
                    <strong><?= e($r['name']) ?></strong>
                    <?php if (!empty($r['label'])): ?>
                      <span class="text-muted small">– <?= e($r['label']) ?></span>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- JOGOSULTSÁGOK (DataTable) -->
      <div class="col-md-6">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fa-solid fa-key"></i> Jogosultságok</h3>
          </div>
          <div class="card-body">
            <?php if (empty($perms)): ?>
              <p class="text-secondary mb-0">Nincsenek jogosultságok.</p>
            <?php else: ?>
              <div class="table-responsive">
                <table id="permTable" class="table table-striped table-sm align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Jogosultság</th>
                      <th>Leírás</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($perms as $p): ?>
                      <tr>
                        <td><code><?= e($p['name']) ?></code></td>
                        <td><?= e($p['label'] ?? '') ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->

<script>
document.addEventListener('DOMContentLoaded', function() {
  if (document.querySelector('#permTable')) {
    new DataTable('#permTable', {
      pageLength: 10,
      order: [[0, 'asc']],
      language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/hu.json' }
    });
  }
});
</script>
