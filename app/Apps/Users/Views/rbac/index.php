<?php
/** @var string $title */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<div class="content-header">
  <div class="container-fluid">
    <h1 class="m-0"><?= e($title ?? 'RBAC') ?></h1>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <div class="alert alert-info">
      <i class="bi bi-shield-lock me-2"></i>
      RBAC (roles & permissions) admin – placeholder page.
      <!-- Later: roles list, permissions list, mappings (user_roles, role_permissions) -->
    </div>

    <div class="card card-outline card-secondary">
      <div class="card-header">
        <h3 class="card-title">Overview</h3>
      </div>
      <div class="card-body">
        <ul class="mb-0">
          <li><strong>Roles</strong>: logical groups (e.g., <code>admin</code>).</li>
          <li><strong>Permissions</strong>: fine-grained rights (e.g., <code>users.view</code>).</li>
          <li><strong>Mappings</strong>: user ↔ roles, role ↔ permissions.</li>
        </ul>
      </div>
      <div class="card-footer text-body-secondary small">
        Coming soon: CRUD for roles/permissions and assignment screens.
      </div>
    </div>

  </div>
</section>
