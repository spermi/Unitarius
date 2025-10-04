<?php
/** @var string $title */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<div class="content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <h1 class="m-0"><?= e($title ?? 'RBAC') ?></h1>
    <div class="btn-group">
      <a class="btn btn-primary btn-sm" href="<?= base_url('/rbac/roles') ?>">Roles</a>
      <a class="btn btn-primary btn-sm" href="<?= base_url('/rbac/permissions') ?>">Permissions</a>
      <a class="btn btn-primary btn-sm" href="<?= base_url('/rbac/assignments') ?>">Assignments</a>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <div class="alert alert-info">
      <i class="bi bi-shield-lock me-2"></i>
      RBAC (roles & permissions) admin – dashboard.
    </div>

    <div class="row g-3">
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Roles</h5>
            <p class="card-text">Define role groups (e.g., <code>admin</code>), then assign them to users.</p>
            <a href="<?= base_url('/rbac/roles') ?>" class="btn btn-outline-primary btn-sm">Open</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Permissions</h5>
            <p class="card-text">Fine-grained rights (e.g., <code>users.view</code>, <code>rbac.manage</code>), assignable to roles.</p>
            <a href="<?= base_url('/rbac/permissions') ?>" class="btn btn-outline-primary btn-sm">Open</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Assignments</h5>
            <p class="card-text">User ↔ Role and Role ↔ Permission mappings overview.</p>
            <a href="<?= base_url('/rbac/assignments') ?>" class="btn btn-outline-primary btn-sm">Open</a>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>
