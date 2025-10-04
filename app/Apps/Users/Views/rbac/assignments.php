assignments<?php
/** @var string $title */
/** @var array<int,array<string,mixed>> $userRoles */
/** @var array<int,array<string,mixed>> $rolePerms */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<div class="content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <h1 class="m-0"><?= e($title ?? 'RBAC – Assignments') ?></h1>
    <div class="btn-group">
      <a class="btn btn-outline-secondary btn-sm" href="<?= base_url('/rbac') ?>">RBAC dashboard</a>
      <a class="btn btn-outline-primary btn-sm" href="<?= base_url('/rbac/roles') ?>">Roles</a>
      <a class="btn btn-outline-primary btn-sm" href="<?= base_url('/rbac/permissions') ?>">Permissions</a>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <!-- User ↔ Role -->
    <div class="card card-outline card-primary">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">User ↔ Role</h3>
        <span class="text-body-secondary small">Total: <?= count($userRoles) ?></span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:90px;">User ID</th>
                <th>User name</th>
                <th>User email</th>
                <th style="width:90px;">Role ID</th>
                <th>Role name</th>
                <th>Role label</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($userRoles)): ?>
                <tr><td colspan="6" class="text-center p-4 text-secondary">No user-role mappings.</td></tr>
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
      <div class="card-footer text-body-secondary small">Read-only. Assignment editor coming soon.</div>
    </div>

    <!-- Role ↔ Permission -->
    <div class="card card-outline card-secondary">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Role ↔ Permission</h3>
        <span class="text-body-secondary small">Total: <?= count($rolePerms) ?></span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:90px;">Role ID</th>
                <th>Role name</th>
                <th>Role label</th>
                <th style="width:120px;">Permission ID</th>
                <th>Permission name</th>
                <th>Permission label</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rolePerms)): ?>
                <tr><td colspan="6" class="text-center p-4 text-secondary">No role-permission mappings.</td></tr>
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
      <div class="card-footer text-body-secondary small">Read-only. Mapping editor coming soon.</div>
    </div>

  </div>
</section>
