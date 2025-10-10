<?php
/** @var string $title */
/** @var array<int,array<string,mixed>> $roles */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <h3 class="mb-0"><?= e($title ?? 'RBAC – Szerepek') ?></h3>
    <div class="btn-group mt-2 mt-sm-0">
      <a class="btn btn-outline-secondary btn-sm" href="<?= base_url('/rbac') ?>">
        <i class="fa-solid fa-shield-halved me-1"></i> RBAC főoldal
      </a>
      <a class="btn btn-outline-primary btn-sm" href="<?= base_url('/rbac/permissions') ?>">
        <i class="fa-solid fa-key me-1"></i> Jogosultságok
      </a>
      <a class="btn btn-outline-primary btn-sm" href="<?= base_url('/rbac/assignments') ?>">
        <i class="fa-solid fa-diagram-project me-1"></i> Hozzárendelések
      </a>
      <a class="btn btn-success btn-sm" href="<?= base_url('/rbac/roles/create') ?>">
        <i class="fa-solid fa-plus me-1"></i> Új szerep
      </a>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">

    <div class="card border-primary-subtle shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="fa-regular fa-id-badge me-1"></i> Szerepek
        </h5>
        <span class="text-body-secondary small">Összesen: <?= count($roles) ?></span>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:90px;">ID</th>
                <th>Név</th>
                <th>Címke</th>
                <th>Létrehozva</th>
                <th>Módosítva</th>
                <th class="text-end" style="width:160px;">Műveletek</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($roles)): ?>
                <tr>
                  <td colspan="6" class="text-center p-4 text-secondary">
                    <i class="fa-regular fa-circle-xmark me-1"></i> Nincsenek szerepek.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($roles as $r): ?>
                  <?php $id = (int)($r['id'] ?? 0); ?>
                  <tr>
                    <td><code><?= $id ?></code></td>
                    <td><span class="fw-semibold"><?= e((string)($r['name'] ?? '')) ?></span></td>
                    <td><?= e((string)($r['label'] ?? '')) ?></td>
                    <td class="text-nowrap"><?= e((string)($r['created_at'] ?? '')) ?: '—' ?></td>
                    <td class="text-nowrap"><?= e((string)($r['updated_at'] ?? '')) ?: '—' ?></td>
                    <td class="text-end">
                      <a class="btn btn-outline-primary btn-sm" href="<?= base_url('/rbac/roles/'.$id.'/edit') ?>">
                        <i class="fa-regular fa-pen-to-square"></i>
                      </a>
                      <form action="<?= base_url('/rbac/roles/'.$id.'/delete') ?>" method="post" class="d-inline"
                            onsubmit="return confirm('Biztosan törlöd a szerepet?');">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                          <i class="fa-regular fa-trash-can"></i>
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
        <i class="fa-regular fa-circle-info me-1"></i> A <strong>műveletek</strong> a gombsoron érhetők el (létrehozás/szerkesztés/törlés).
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->
