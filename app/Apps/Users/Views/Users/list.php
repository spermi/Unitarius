<?php
/** @var string $title */
/** @var array<int,array<string,mixed>> $users */

$defaultAvatar = base_url('public/assets/adminlte/img/user.png');

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h3 class="mb-0"><?= e($title ?? 'Felhasználók') ?></h3>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Vezérlőpult</a></li>
          <li class="breadcrumb-item active">Felhasználók</li>
        </ol>
      </div>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">

    <div class="card card-outline card-primary">
      <div class="card-header">
        <h3 class="card-title">Felhasználók listája</h3>
        <div class="card-tools"><!-- reserved for filters/search later --></div>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:64px;">Avatar</th>
                <th>Név</th>
                <th>Email</th>
                <th>Státusz</th>
                <th>Utolsó bejelentkezés</th>
                <th class="text-end" style="width:90px;">ID</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($users)): ?>
                <tr>
                  <td colspan="6" class="text-center p-4 text-secondary">Nincs felhasználó.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($users as $u): ?>
                  <?php
                    $name   = e((string)($u['name'] ?? ''));
                    $email  = e((string)($u['email'] ?? ''));
                    $id     = (int)($u['id'] ?? 0);
                    $status = (int)($u['status'] ?? 0);
                    $last   = (string)($u['last_login_at'] ?? '');
                    $avRaw  = (string)($u['avatar'] ?? '');
                    $avatar = $avRaw !== '' ? e($avRaw) : $defaultAvatar;
                  ?>
                  <tr>
                    <td>
                      <img
                        src="<?= $avatar ?>"
                        alt="avatar"
                        class="rounded-circle"
                        width="48" height="48"
                        loading="lazy"
                        referrerpolicy="no-referrer"
                        style="object-fit:cover;"
                      >
                    </td>
                    <td><?= $name !== '' ? $name : '<span class="text-secondary">—</span>' ?></td>
                    <td><a href="mailto:<?= $email ?>"><?= $email !== '' ? $email : '<span class="text-secondary">—</span>' ?></a></td>
                    <td>
                      <?php if ($status === 1): ?>
                        <span class="badge bg-success">Aktív</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">Inaktív</span>
                      <?php endif; ?>
                    </td>
                    <td><?= $last !== '' ? e($last) : '<span class="text-secondary">—</span>' ?></td>
                    <td class="text-end"><code><?= $id ?></code></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer text-body-secondary small">
        Összesen: <?= count($users) ?> elem.
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->
