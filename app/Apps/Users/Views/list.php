<?php
/** @var string $title */
/** @var array<int,array<string,mixed>> $users */

$defaultAvatar = base_url('public/assets/adminlte/img/user.png');

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<!-- DataTables (CDN version) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h3 class="mb-0"><?= e($title ?? 'Felhasználók') ?></h3>
      </div>
      <div class="col-sm-6 text-end">
        <?php if (function_exists('can') && can('users.create')): ?>
          <a href="<?= base_url('/users/new') ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-user-plus"></i> Új Felhasználó
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

    <div class="card card-outline card-primary">
      <div class="card-body">
        <div class="table-responsive">
          <table id="usersTable" class="table table-striped table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:64px;">Avatar</th>
                <th>Név</th>
                <th>Email</th>
                <th>Státusz</th>
                <th>Utolsó bejelentkezés</th>
                <th class="text-end" style="width:90px;">Műveletek</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <?php
                  $id     = (int)($u['id'] ?? 0);
                  $name   = e((string)($u['name'] ?? ''));
                  $email  = e((string)($u['email'] ?? ''));
                  $status = (int)($u['status'] ?? 0);
                  $last   = $u['last_login_at'] ? date('Y-m-d H:i', strtotime($u['last_login_at'])) : '—';
                  $avatar = !empty($u['avatar']) ? e($u['avatar']) : $defaultAvatar;
                ?>
                <tr>
                  <td><img src="<?= $avatar ?>" class="rounded-circle" width="48" height="48" style="object-fit:cover;" referrerpolicy="no-referrer"></td>
                  <td><a href="<?= base_url('/users/' . $id . '/view') ?>" class="text-decoration-none"><?= $name ?></a></td>
                  <td><a href="mailto:<?= $email ?>"><?= $email ?></a></td>
                  <td><?= $status ? '<span class="badge bg-success">Aktív</span>' : '<span class="badge bg-secondary">Inaktív</span>' ?></td>
                  <td><?= e($last) ?></td>
                  <td class="text-end">
                    <?php if (can('users.manage')): ?>
                      <a href="<?= base_url('/users/' . $id . '/edit') ?>" class="btn btn-sm btn-primary" title="Szerkesztés">
                        <i class="fa-solid fa-pen-to-square"></i>
                      </a>
                    <?php endif; ?>
                    <?php if (can('users.delete')): ?>
                      <form method="post" action="<?= base_url('/users/' . $id . '/delete') ?>" class="d-inline" onsubmit="return confirm('Biztosan törlöd ezt a felhasználót?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger" title="Törlés">
                          <i class="fa-solid fa-trash-can"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->

<!-- DataTables init -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  new DataTable('#usersTable', {
    pageLength: 10,
    order: [[1, 'asc']],
    language: {
      url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/hu.json'
    },
    columnDefs: [
      { orderable: false, targets: [0, 5] } // avatar + műveletek oszlop nem rendezhető
    ]
  });
});
</script>
