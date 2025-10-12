<?php
/** @var array<string,mixed> $user */
/** @var array<int,array<string,mixed>> $roles */
/** @var array<int,array<string,mixed>> $perms */

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$avatar = !empty($user['avatar'])
    ? e($user['avatar'])
    : base_url('public/assets/adminlte/img/user.png');

$statusBadge = $user['status'] == 1
    ? '<span class="badge bg-success">Aktív</span>'
    : '<span class="badge bg-secondary">Inaktív</span>';
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h3 class="mb-0">Saját profilom</h3>
      </div>
      <div class="col-sm-6 text-end">
        <a href="<?= base_url('/dashboard') ?>" class="btn btn-secondary btn-sm">
          <i class="fa-solid fa-arrow-left"></i> Vissza
        </a>
      </div>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">

    <?php if (function_exists('flash_display')): ?>
      <?= flash_display() ?>
    <?php endif; ?>

    <div class="card card-outline card-primary mb-4 shadow-sm">
      <div class="card-body d-flex align-items-center">
        <img src="<?= $avatar ?>" alt="avatar" class="rounded-circle me-4 shadow-sm"
             width="96" height="96" referrerpolicy="no-referrer" style="object-fit:cover;">
        <div>
          <h4 class="mb-1"><?= e($user['name'] ?? '') ?></h4>
          <p class="mb-1 text-muted"><?= e($user['email'] ?? '') ?></p>
          <p class="mb-0">Státusz: <?= $statusBadge ?></p>
          <small class="text-secondary">
            Utolsó bejelentkezés: <?= e($user['last_login_at'] ?? '—') ?><br>
            Létrehozva: <?= e($user['created_at'] ?? '—') ?><br>
            Módosítva: <?= e($user['updated_at'] ?? '—') ?>
          </small>
        </div>
      </div>
    </div>

    <!-- Szerkesztési űrlap -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-primary text-white">
        <h3 class="card-title mb-0"><i class="fa-solid fa-pen-to-square"></i> Profil szerkesztése</h3>
      </div>
      <form method="post" action="<?= base_url('/profile') ?>" class="card-body">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label for="name" class="form-label">Név</label>
          <input type="text" name="name" id="name" class="form-control"
                 value="<?= e($user['name'] ?? '') ?>" maxlength="255" required>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label text-muted">E-mail cím (nem módosítható)</label>
          <input type="email" id="email" class="form-control text-muted bg-light" 
                 value="<?= e($user['email'] ?? '') ?>" readonly>
        </div>

        <div class="mb-3">
          <label for="avatar" class="form-label">Avatar URL</label>
          <input type="url" name="avatar" id="avatar" class="form-control"
                 value="<?= e($user['avatar'] ?? '') ?>" maxlength="255">
          <small class="form-text text-muted">Megadhatsz külső képlinket vagy üresen is hagyhatod.</small>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-success">
            <i class="fa-solid fa-save"></i> Mentés
          </button>
        </div>
      </form>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0"><i class="fa-solid fa-user-shield"></i> Szerepek</h3>
          </div>
          <div class="card-body">
            <?php if (empty($roles)): ?>
              <p class="text-secondary mb-0">Nincsenek hozzárendelt szerepek.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($roles as $r): ?>
                  <li class="list-group-item">
                    <i class="fa-regular fa-circle-check text-primary me-2"></i>
                    <strong><?= e($r['name']) ?></strong>
                    <?php if (!empty($r['label'])): ?>
                      <span class="text-muted small">– <?= e($r['label']) ?></span>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0"><i class="fa-solid fa-key"></i> Jogosultságok</h3>
          </div>
          <div class="card-body">
            <?php if (empty($perms)): ?>
              <p class="text-secondary mb-0">Nincsenek jogosultságok.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush small">
                <?php foreach ($perms as $p): ?>
                  <li class="list-group-item">
                    <code><?= e($p['name']) ?></code>
                    <?php if (!empty($p['label'])): ?>
                      <span class="text-muted">– <?= e($p['label']) ?></span>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->
