<?php
/** @var string $title */
/** @var array<int,array<string,mixed>> $roles */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<div class="content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <h1 class="m-0"><?= e($title ?? 'RBAC – Roles') ?></h1>
    <div class="btn-group">
      <a class="btn btn-outline-secondary btn-sm" href="<?= base_url('/rbac') ?>">RBAC dashboard</a>
      <a class="btn btn-outline-primary btn-sm" href="<?= base_url('/rbac/permissions') ?>">Permissions</a>
      <a class="btn btn-outline-primary btn-sm" href="<?= base_url('/rbac/assignments') ?>">Assignments</a>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <div class="card card-outline card-primary">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Roles</h3>
        <span class="text-body-secondary small">Total: <?= count($roles) ?></span>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:90px;">ID</th>
                <th>Name</th>
                <th>Label</th>
                <th>Created</th>
                <th>Updated</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($roles)): ?>
                <tr><td colspan="5" class="text-center p-4 text-secondary">No roles found.</td></tr>
              <?php else: ?>
                <?php foreach ($roles as $r): ?>
                  <tr>
                    <td><code><?= (int)($r['id'] ?? 0) ?></code></td>
                    <td><span class="fw-semibold"><?= e((string)($r['name'] ?? '')) ?></span></td>
                    <td><?= e((string)($r['label'] ?? '')) ?></td>
                    <td class="text-nowrap"><?= e((string)($r['created_at'] ?? '')) ?: '—' ?></td>
                    <td class="text-nowrap"><?= e((string)($r['updated_at'] ?? '')) ?: '—' ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer text-body-secondary small">
        Read-only view. CRUD coming soon.
      </div>
    </div>

  </div>
</section>
