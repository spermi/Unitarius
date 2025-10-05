<?php
/** @var string $title */
/** @var array<int,array<string,mixed>> $permissions */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <h3 class="mb-0"><?= e($title ?? 'RBAC – Jogosultságok') ?></h3>
    <div class="btn-group mt-2 mt-sm-0">
      <a class="btn btn-outline-secondary btn-sm" href="<?= base_url('/rbac') ?>">
        <i class="fa-solid fa-shield-halved me-1"></i> RBAC főoldal
      </a>
      <a class="btn btn-outline-primary btn-sm" href="<?= base_url('/rbac/roles') ?>">
        <i class="fa-regular fa-id-badge me-1"></i> Szerepek
      </a>
      <a class="btn btn-outline-primary btn-sm" href="<?= base_url('/rbac/assignments') ?>">
        <i class="fa-solid fa-diagram-project me-1"></i> Hozzárendelések
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
          <i class="fa-solid fa-key me-1"></i> Jogosultságok
        </h5>
        <span class="text-body-secondary small">Összesen: <?= count($permissions) ?></span>
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
              </tr>
            </thead>
            <tbody>
              <?php if (empty($permissions)): ?>
                <tr>
                  <td colspan="5" class="text-center p-4 text-secondary">
                    <i class="fa-regular fa-circle-xmark me-1"></i> Nincsenek jogosultságok.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($permissions as $p): ?>
                  <tr>
                    <td><code><?= (int)($p['id'] ?? 0) ?></code></td>
                    <td><span class="fw-semibold"><?= e((string)($p['name'] ?? '')) ?></span></td>
                    <td><?= e((string)($p['label'] ?? '')) ?></td>
                    <td class="text-nowrap"><?= e((string)($p['created_at'] ?? '')) ?: '—' ?></td>
                    <td class="text-nowrap"><?= e((string)($p['updated_at'] ?? '')) ?: '—' ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer text-body-secondary small">
        <i class="fa-regular fa-lock me-1"></i> Jelenleg csak olvasható nézet — a CRUD funkciók hamarosan elérhetők.
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->
