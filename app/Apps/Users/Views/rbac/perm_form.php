<?php
/** @var string $title */
/** @var array<string,mixed>|null $perm */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$isEdit = is_array($perm ?? null);
$action = $isEdit
    ? base_url('/rbac/permissions/' . (int)($perm['id'] ?? 0) . '/edit')
    : base_url('/rbac/permissions/create');

$name  = $isEdit ? (string)($perm['name']  ?? '') : '';
$label = $isEdit ? (string)($perm['label'] ?? '') : '';
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <h3 class="mb-0"><?= e($title ?? ($isEdit ? 'Jogosultság szerkesztése' : 'Új jogosultság')) ?></h3>
    <div class="btn-group mt-2 mt-sm-0">
      <a class="btn btn-outline-secondary btn-sm" href="<?= base_url('/rbac/permissions') ?>">
        <i class="fa-solid fa-key me-1"></i> Vissza a jogosultságokhoz
      </a>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">
    <div class="card border-primary-subtle shadow-sm" style="font-size:14px;">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="fa-solid fa-key me-1"></i> <?= $isEdit ? 'Jogosultság szerkesztése' : 'Új jogosultság' ?>
        </h5>
      </div>

      <form action="<?= $action ?>" method="post" autocomplete="off" novalidate>
        <div class="card-body">
          <div class="mb-3">
            <label for="name" class="form-label">Név (namespace)</label>
            <input type="text" id="name" name="name" class="form-control" required value="<?= e($name) ?>"
                   placeholder="pl.: users.view, users.manage, rbac.manage">
            <div class="form-text">Kötelező, egyedi. Ajánlott a <code>modul.művelet</code> séma.</div>
          </div>

          <div class="mb-3">
            <label for="label" class="form-label">Címke</label>
            <input type="text" id="label" name="label" class="form-control" required value="<?= e($label) ?>"
                   placeholder="pl.: Felhasználók megtekintése">
            <div class="form-text">Kötelező, emberi olvasóbarát megnevezés.</div>
          </div>
        </div>

        <div class="card-footer d-flex justify-content-between">
          <a href="<?= base_url('/rbac/permissions') ?>" class="btn btn-outline-secondary">
            <i class="fa-regular fa-circle-left me-1"></i> Mégse
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fa-regular fa-floppy-disk me-1"></i> Mentés
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<!--end::App Content-->
