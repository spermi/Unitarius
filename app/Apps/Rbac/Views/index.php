<?php
/** @var string $title */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <h3 class="mb-0"><?= e($title ?? 'RBAC') ?></h3>
    <div class="btn-group mt-2 mt-sm-0">
      <a class="btn btn-primary btn-sm" href="<?= base_url('/rbac/roles') ?>">
        <i class="fa-solid fa-id-badge me-1"></i> Szerepek
      </a>
      <a class="btn btn-primary btn-sm" href="<?= base_url('/rbac/permissions') ?>">
        <i class="fa-solid fa-key me-1"></i> Jogosultságok
      </a>
      <a class="btn btn-primary btn-sm" href="<?= base_url('/rbac/assignments') ?>">
        <i class="fa-solid fa-diagram-project me-1"></i> Hozzárendelések
      </a>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">

    <div class="alert alert-info d-flex align-items-center">
      <i class="fa-solid fa-shield-halved me-2"></i>
      RBAC (szerepek és jogosultságok) adminisztrációs központ.
    </div>

    <div class="row g-3">
      <div class="col-md-4">
        <div class="card h-100 border-primary-subtle">
          <div class="card-body">
            <h5 class="card-title"><i class="fa-regular fa-id-badge me-1"></i> Szerepek</h5>
            <p class="card-text">
              Hozz létre és kezeld a szerepeket (pl. <code>admin</code>), majd rendeld hozzá a felhasználókhoz.
            </p>
            <a href="<?= base_url('/rbac/roles') ?>" class="btn btn-outline-primary btn-sm">Megnyitás</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 border-primary-subtle">
          <div class="card-body">
            <h5 class="card-title"><i class="fa-regular fa-keyboard me-1"></i> Jogosultságok</h5>
            <p class="card-text">
              Finomhangolt hozzáférési jogok (pl. <code>users.view</code>, <code>rbac.manage</code>), szerepekhez rendelhetők.
            </p>
            <a href="<?= base_url('/rbac/permissions') ?>" class="btn btn-outline-primary btn-sm">Megnyitás</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 border-primary-subtle">
          <div class="card-body">
            <h5 class="card-title"><i class="fa-regular fa-diagram-project me-1"></i> Hozzárendelések</h5>
            <p class="card-text">
              Felhasználó ↔ Szerep és Szerep ↔ Jogosultság kapcsolatok áttekintése.
            </p>
            <a href="<?= base_url('/rbac/assignments') ?>" class="btn btn-outline-primary btn-sm">Megnyitás</a>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->
