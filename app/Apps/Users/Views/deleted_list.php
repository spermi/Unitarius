<?php
/** @var string $title */
/** @var array<int,array<string,mixed>> $users */

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h3 class="mb-0 text-danger">
          <i class="fa-solid fa-user-shield me-2"></i><?= e($title ?? 'Törölt / inaktív felhasználók') ?>
        </h3>
      </div>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">

    <div class="card border-danger shadow-sm">
      <div class="card-header bg-danger text-white">
        <h3 class="card-title"><i class="fa-solid fa-user-slash me-2"></i>Törölt / inaktív felhasználók</h3>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-danger">
              <tr>
                <th>ID</th>
                <th>Név</th>
                <th>Email</th>
                <th>Státusz</th>
                <th>Törlés ideje</th>
                <th class="text-end" style="width:140px;">Műveletek</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($users)): ?>
                <tr>
                  <td colspan="6" class="text-center p-4 text-secondary">
                    <i class="fa-solid fa-circle-check me-1"></i> Nincsenek törölt vagy inaktív felhasználók.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($users as $u): ?>
                  <?php
                    $id   = (int)($u['id'] ?? 0);
                    $name = e((string)($u['name'] ?? ''));
                    $email = e((string)($u['email'] ?? ''));
                    $status = (int)($u['status'] ?? 0);
                    $deleted = (int)($u['deleted'] ?? 0);
                    $deletedAt = e((string)($u['deleted_at'] ?? ''));

                    $badge = $deleted
                      ? '<span class="badge bg-danger">Törölt</span>'
                      : ($status === 0
                          ? '<span class="badge bg-secondary">Inaktív</span>'
                          : '<span class="badge bg-success">Aktív</span>');
                  ?>
                  <tr class="<?= $deleted ? 'table-danger' : 'table-secondary' ?>">
                    <td><?= $id ?></td>
                    <td><?= $name ?: '<span class="text-muted">—</span>' ?></td>
                    <td><?= $email ?: '<span class="text-muted">—</span>' ?></td>
                    <td><?= $badge ?></td>
                    <td><?= $deletedAt !== '' ? $deletedAt : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-end">
                      <form method="post" action="<?= base_url('/users/' . $id . '/restore') ?>"
                            onsubmit="return confirm('Biztosan visszaállítod ezt a felhasználót?');"
                            class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-success">
                          <i class="fa-solid fa-rotate-left"></i> Visszaállítás
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer text-body-secondary small">
        Összesen: <?= count($users) ?> felhasználó.
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->
