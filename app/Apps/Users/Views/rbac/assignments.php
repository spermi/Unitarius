<?php
/** @var string $title */
/** @var array<int,array<string,mixed>> $userRoles */
/** @var array<int,array<string,mixed>> $rolePerms */
/** @var array<int,array<string,mixed>> $users */
/** @var array<int,array<string,mixed>> $roles */
/** @var array<int,array<string,mixed>> $permissions */
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
    <div class="card border-primary-subtle shadow-sm mb-4" style="font-size:14px;">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="fa-solid fa-user-tag me-1"></i> Felhasználó ↔ Szerep
        </h5>
        <span class="text-body-secondary small">Összesen: <?= count($userRoles) ?></span>
      </div>

      <!-- Attach form (User ↔ Role) -->
      <div class="card-body border-bottom">
        <form action="<?= base_url('/rbac/assignments/attach') ?>" method="post" class="row g-2 align-items-end">
          <input type="hidden" name="type" value="user_role">
          <div class="col-12 col-md-5">
            <label class="form-label">Felhasználó</label>
            <select name="user_id" class="form-select" required>
              <option value="">– válassz felhasználót –</option>
              <?php foreach (($users ?? []) as $u): ?>
                <option value="<?= (int)$u['id'] ?>">
                  <?= e((string)($u['name'] ?? $u['email'] ?? '')) ?> (<?= e((string)($u['email'] ?? '')) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-5">
            <label class="form-label">Szerep</label>
            <select name="role_id" class="form-select" required>
              <option value="">– válassz szerepet –</option>
              <?php foreach (($roles ?? []) as $r): ?>
                <option value="<?= (int)$r['id'] ?>">
                  <?= e((string)$r['name']) ?> — <?= e((string)$r['label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-2 text-end">
            <button type="submit" class="btn btn-primary w-100">
              <i class="fa-solid fa-link me-1"></i> Hozzárendelés
            </button>
          </div>
        </form>
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
                <th class="text-end" style="width:120px;">Műveletek</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($userRoles)): ?>
                <tr>
                  <td colspan="7" class="text-center p-4 text-secondary">
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
                    <td class="text-end">
                      <form action="<?= base_url('/rbac/assignments/detach') ?>" method="post" class="d-inline"
                            onsubmit="return confirm('Eltávolítod ezt a hozzárendelést?');">
                        <input type="hidden" name="type" value="user_role">
                        <input type="hidden" name="user_id" value="<?= (int)($ur['user_id'] ?? 0) ?>">
                        <input type="hidden" name="role_id" value="<?= (int)($ur['role_id'] ?? 0) ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                          <i class="fa-regular fa-circle-xmark"></i>
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

    </div>

    <!-- Role ↔ Permission -->
    <div class="card border-secondary-subtle shadow-sm" style="font-size:14px;">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="fa-solid fa-diagram-project me-1"></i> Szerep ↔ Jogosultság
        </h5>
        <span class="text-body-secondary small">Összesen: <?= count($rolePerms) ?></span>
      </div>

      <!-- Attach form (Role ↔ Permission) -->
      <div class="card-body border-bottom">
        <form action="<?= base_url('/rbac/assignments/attach') ?>" method="post" class="row g-2 align-items-end">
          <input type="hidden" name="type" value="role_perm">
          <div class="col-12 col-md-5">
            <label class="form-label">Szerep</label>
            <select name="role_id" class="form-select" required>
              <option value="">– válassz szerepet –</option>
              <?php foreach (($roles ?? []) as $r): ?>
                <option value="<?= (int)$r['id'] ?>">
                  <?= e((string)$r['name']) ?> — <?= e((string)$r['label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-5">
            <label class="form-label">Jogosultság</label>
            <select name="permission_id" class="form-select" required>
              <option value="">– válassz jogosultságot –</option>
              <?php foreach (($permissions ?? []) as $p): ?>
                <option value="<?= (int)$p['id'] ?>">
                  <?= e((string)$p['name']) ?> — <?= e((string)$p['label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-2 text-end">
            <button type="submit" class="btn btn-primary w-100">
              <i class="fa-solid fa-link me-1"></i> Hozzárendelés
            </button>
          </div>
        </form>
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
                <th class="text-end" style="width:120px;">Műveletek</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rolePerms)): ?>
                <tr>
                  <td colspan="7" class="text-center p-4 text-secondary">
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
                    <td class="text-end">
                      <form action="<?= base_url('/rbac/assignments/detach') ?>" method="post" class="d-inline"
                            onsubmit="return confirm('Eltávolítod ezt a hozzárendelést?');">
                        <input type="hidden" name="type" value="role_perm">
                        <input type="hidden" name="role_id" value="<?= (int)($rp['role_id'] ?? 0) ?>">
                        <input type="hidden" name="permission_id" value="<?= (int)($rp['permission_id'] ?? 0) ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                          <i class="fa-regular fa-circle-xmark"></i>
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

    </div>

  </div>
</div>
<!--end::App Content-->
