<?php
/** @var string $title */
/** @var array<int,array<string,mixed>> $userRoles */
/** @var array<int,array<string,mixed>> $rolePerms */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <h3 class="mb-0"><?= e($title ?? 'RBAC – Hozzárendelések') ?></h3>
    <div class="btn-group mt-2 mt-sm-0">
      <a class="btn btn-outline-secondary btn-sm" href="<?= base_url('/rbac') ?>">
        <i class="fa-solid fa-shield-halved me-1"></i> RBAC főoldal
      </a>
      <a class="btn btn-outline-primary btn-sm" href="<?= base_url('/rbac/roles') ?>">
        <i class="fa-regular fa-id-badge me-1"></i> Szerepek
      </a>
      <a class="btn btn-outline-primary btn-sm" href="<?= base_url('/rbac/permissions') ?>">
        <i class="fa-solid fa-key me-1"></i> Jogosultságok
      </a>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">

    <!-- User ↔ Role -->
    <div class="card border-primary-subtle shadow-sm mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="fa-solid fa-user-tag me-1"></i> Felhasználó ↔ Szerep
        </h5>
        <span class="text-body-secondary small">Összesen: <?= count($userRoles) ?></span>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:90px;">User ID</th>
                <th>Név</th>
                <th>E-mail</th>
                <th style="width:90px;">Role ID</th>
                <th>Szerep neve</th>
                <th>Szerep címke</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($userRoles)): ?>
                <tr>
                  <td colspan="6" class="text-center p-4 text-secondary">
                    <i class="fa-regular fa-circle-xmark me-1"></i> Nincs felhasználó–szerep hozzárendelés.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($userRoles as $ur): ?>
                  <tr>
                    <td><code><?= (int)($ur['user_id'] ?? 0) ?></code></td>
                    <td><?= e((string)($ur['user_name'] ?? '')) ?></td>
                    <td><a href="mailto:<?= e((string)($ur['user_email'] ?? '')) ?>"><?= e((string)($ur['user_email'] ?? '')) ?></a></td>
                    <td><code><?= (int)($ur['role_id'] ?? 0) ?></code></td>
                    <td><span class="fw-semibold"><?= e((string)($ur['role_name'] ?? '')) ?></span></td>
                    <td><?= e((string)($ur['role_label'] ?? '')) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer text-body-secondary small">
        Olvasható nézet. Szerkesztés (CRUD) hamarosan.
      </div>
    </div>

    <!-- Role ↔ Permission -->
    <div class="card border-secondary-subtle shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="fa-solid fa-diagram-project me-1"></i> Szerep ↔ Jogosultság
        </h5>
        <span class="text-body-secondary small">Összesen: <?= count($rolePerms) ?></span>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:90px;">Role ID</th>
                <th>Szerep neve</th>
                <th>Szerep címke</th>
                <th style="width:120px;">Permission ID</th>
                <th>Jogosultság neve</th>
                <th>Jogosultság címke</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rolePerms)): ?>
                <tr>
                  <td colspan="6" class="text-center p-4 text-secondary">
                    <i class="fa-regular fa-circle-xmark me-1"></i> Nincs szerep–jogosultság hozzárendelés.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($rolePerms as $rp): ?>
                  <tr>
                    <td><code><?= (int)($rp['role_id'] ?? 0) ?></code></td>
                    <td><span class="fw-semibold"><?= e((string)($rp['role_name'] ?? '')) ?></span></td>
                    <td><?= e((string)($rp['role_label'] ?? '')) ?></td>
                    <td><code><?= (int)($rp['permission_id'] ?? 0) ?></code></td>
                    <td><span class="fw-semibold"><?= e((string)($rp['perm_name'] ?? '')) ?></span></td>
                    <td><?= e((string)($rp['perm_label'] ?? '')) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer text-body-secondary small">
        Olvasható nézet. Szerkesztés (CRUD) hamarosan.
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->
