<?php
/** @var string $title */
/** @var array<string,mixed>|null $user */
/** @var string|null $action */

function e(string $v): string {
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$actionUrl = $action ?? base_url('/users');
$id = (int)($user['id'] ?? 0);
$name = e((string)($user['name'] ?? ''));
$email = e((string)($user['email'] ?? ''));
$status = (int)($user['status'] ?? 0);
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h3 class="mb-0"><?= e($title ?? 'Felhasználó szerkesztése') ?></h3>
      </div>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">

    <div class="card card-outline card-primary shadow-sm">
      <div class="card-header">
        <h3 class="card-title">
          <?= $id ? 'Felhasználó szerkesztése (ID: ' . $id . ')' : 'Új felhasználó' ?>
        </h3>
      </div>

      <form method="post" action="<?= e($actionUrl) ?>">
        <?= csrf_field() ?>

        <div class="card-body">

          <div class="mb-3">
            <label for="name" class="form-label">Név</label>
            <input type="text" class="form-control" id="name" name="name"
                   value="<?= $name ?>" required maxlength="255">
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">E-mail cím</label>
            <input type="email" class="form-control" id="email" name="email"
                   value="<?= $email ?>" required maxlength="255">
          </div>

          <?php if ($id === 0): ?>
            <div class="mb-3">
              <label for="password" class="form-label">Jelszó</label>
              <input type="password" class="form-control" id="password" name="password"
                     minlength="6" maxlength="255" required>
              <div class="form-text">Minimum 6 karakter, csak új felhasználó létrehozásakor kötelező.</div>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label for="status" class="form-label">Státusz</label>
            <select class="form-select" id="status" name="status">
              <option value="1" <?= $status === 1 ? 'selected' : '' ?>>Aktív</option>
              <option value="0" <?= $status === 0 ? 'selected' : '' ?>>Inaktív</option>
            </select>
          </div>

            <?php
            $isPastor = isset($user['is_pastor'])
                ? filter_var($user['is_pastor'], FILTER_VALIDATE_BOOLEAN)
                : false;
            ?>

            <div class="mb-3">
              <label for="is_pastor" class="form-label">Lelkész státusz</label>

              <?php if (!empty($user['is_pastor'])): ?>
                  <!-- A user már lelkész: a checkbox le van tiltva, de a hidden input elküldi az értéket -->
                  <input type="hidden" name="is_pastor" value="1">
                  <div class="form-check">
                      <input type="checkbox" id="is_pastor" class="form-check-input" checked disabled>
                      <label for="is_pastor" class="form-check-label text-muted">A felhasználó már lelkész (nem módosítható)</label>
                  </div>
              <?php else: ?>
                  <!-- Még nem lelkész: normálul szerkeszthető -->
                  <div class="form-check">
                      <input type="checkbox" id="is_pastor" name="is_pastor" value="1" class="form-check-input">
                      <label for="is_pastor" class="form-check-label">Lelkészi státusz beállítása</label>
                  </div>
              <?php endif; ?>
          </div>

        </div>

        <div class="card-footer text-end">
          <a href="<?= base_url('/users') ?>" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Vissza
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-save"></i> Mentés
          </button>
        </div>
      </form>
    </div>

  </div>
</div>
<!--end::App Content-->
