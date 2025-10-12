<?php
/** @var string $title */
/** @var array<int,array<string,mixed>> $userRoles */
/** @var array<int,array<string,mixed>> $rolePerms */
/** @var array<int,array<string,mixed>> $users */
/** @var array<int,array<string,mixed>> $roles */
/** @var array<int,array<string,mixed>> $permissions */
/** @var array<string,int>|null $urPager */
/** @var array<string,int>|null $rpPager */
/** @var string|null $urSort */
/** @var string|null $urDir */
/** @var string|null $rpSort */
/** @var string|null $rpDir */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// --- Pager helpers (defaults if controller does not send) ---
$ur = $urPager ?? ['page'=>1,'per'=>count($userRoles), 'total'=>count($userRoles), 'pages'=>1];
$rp = $rpPager ?? ['page'=>1,'per'=>count($rolePerms), 'total'=>count($rolePerms), 'pages'=>1];

// --- Sorting (defaults if controller nem küldte) ---
$urSort = isset($urSort) ? (string)$urSort : (string)($_GET['ur_sort'] ?? 'user_id');
$urDir  = strtolower(isset($urDir) ? (string)$urDir : (string)($_GET['ur_dir'] ?? 'asc'));
$urDir  = $urDir === 'desc' ? 'desc' : 'asc';

$rpSort = isset($rpSort) ? (string)$rpSort : (string)($_GET['rp_sort'] ?? 'role_name');
$rpDir  = strtolower(isset($rpDir) ? (string)$rpDir : (string)($_GET['rp_dir'] ?? 'asc'));
$rpDir  = $rpDir === 'desc' ? 'desc' : 'asc';

// Build URL while preserving existing query params
$__assign_base = base_url('/rbac/assignments');
$__assign_qs   = $_GET ?? [];
$buildUrl = function(array $merge) use ($__assign_base, $__assign_qs): string {
  $q = array_merge($__assign_qs, $merge);
  $query = http_build_query($q);
  return $__assign_base . ($query ? ('?' . $query) : '');
};

// Sort header builder
$sortHead = function(string $table, string $key, string $label) use ($buildUrl, $urSort, $urDir, $rpSort, $rpDir): string {
  if ($table === 'ur') {
    $is  = ($urSort === $key);
    $dir = $is ? ($urDir === 'asc' ? 'desc' : 'asc') : 'asc';
    $url = $buildUrl(['ur_sort'=>$key, 'ur_dir'=>$dir, 'ur_page'=>1]);
    $caret = $is ? ($urDir === 'asc' ? 'fa-caret-up' : 'fa-caret-down') : 'fa-sort';
  } else {
    $is  = ($rpSort === $key);
    $dir = $is ? ($rpDir === 'asc' ? 'desc' : 'asc') : 'asc';
    $url = $buildUrl(['rp_sort'=>$key, 'rp_dir'=>$dir, 'rp_page'=>1]);
    $caret = $is ? ($rpDir === 'asc' ? 'fa-caret-up' : 'fa-caret-down') : 'fa-sort';
  }
  return '<a class="text-decoration-none text-reset" href="' . e($url) . '">'
       . e($label) . ' <i class="fa-solid ' . $caret . ' ms-1 opacity-75"></i></a>';
};

// Range text helper
$rangeText = function(array $p): string {
  $total = (int)($p['total'] ?? 0);
  $per   = max(1, (int)($p['per'] ?? 1));
  $page  = max(1, (int)($p['page'] ?? 1));
  if ($total <= 0) return '0 / 0';
  $start = (($page - 1) * $per) + 1;
  $end   = min($total, $page * $per);
  return $start . '–' . $end . ' / ' . $total;
};
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <h3 class="mb-0"><?= e($title ?? 'RBAC – Hozzárendelések') ?></h3>
    <div class="btn-group mt-2 mt-sm-0">
      <a class="btn btn-secondary btn-sm" href="<?= base_url('/rbac') ?>">
        <i class="fa-solid fa-shield-halved me-1"></i> RBAC főoldal
      </a>
      <a class="btn btn-primary btn-sm" href="<?= base_url('/rbac/roles') ?>">
        <i class="fa-regular fa-id-badge me-1"></i> Szerepek
      </a>
      <a class="btn btn-primary btn-sm" href="<?= base_url('/rbac/permissions') ?>">
        <i class="fa-solid fa-key me-1"></i> Jogosultságok
      </a>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">

    <!-- Attach forms row -->
    <div class="row g-3 mb-4">
      <!-- User ↔ Role attach -->
      <div class="col-12 col-lg-6">
        <div class="card border-success-subtle shadow-sm">
          <div class="card-header">
            <strong><i class="fa-solid fa-user-plus me-1"></i> Felhasználóhoz szerep hozzárendelése</strong>
          </div>
          <form method="post" action="<?= base_url('/rbac/assignments/attach') ?>" class="p-3">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="user_role">
            <div class="row g-2">
              <div class="col-12">
                <label class="form-label">Felhasználó</label>
                <select name="user_id" class="form-select js-choices" required>
                  <option value="">– válassz –</option>
                  <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u['id'] ?>">
                      <?= e((string)($u['name'] ?? $u['email'] ?? '')) ?> (<?= e((string)$u['email'] ?? '') ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Szerep</label>
                <select name="role_id" class="form-select js-choices" required>
                  <option value="">– válassz –</option>
                  <?php foreach ($roles as $r): ?>
                    <option value="<?= (int)$r['id'] ?>">
                      <?= e((string)($r['name'] ?? '')) ?> — <?= e((string)($r['label'] ?? '')) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="mt-3 d-flex justify-content-end">
              <button class="btn btn-success">
                <i class="fa-solid fa-plus me-2"></i> Hozzáadás
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Role ↔ Permission attach -->
      <div class="col-12 col-lg-6">
        <div class="card border-primary-subtle shadow-sm">
          <div class="card-header">
            <strong><i class="fa-solid fa-key me-1"></i> Jogosultság hozzárendelése szerephez</strong>
          </div>
        <form method="post" action="<?= base_url('/rbac/assignments/attach') ?>" class="p-3">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="role_perm">
            <div class="row g-2">
              <div class="col-12">
                <label class="form-label">Szerep</label>
                <select name="role_id" class="form-select js-choices" required>
                  <option value="">– válassz –</option>
                  <?php foreach ($roles as $r): ?>
                    <option value="<?= (int)$r['id'] ?>">
                      <?= e((string)($r['name'] ?? '')) ?> — <?= e((string)($r['label'] ?? '')) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Jogosultság</label>
                <select name="permission_id" class="form-select js-choices" required>
                  <option value="">– válassz –</option>
                  <?php foreach ($permissions as $p): ?>
                    <option value="<?= (int)$p['id'] ?>">
                      <?= e((string)($p['name'] ?? '')) ?> — <?= e((string)($p['label'] ?? '')) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="mt-3 d-flex justify-content-end">
              <button class="btn btn-primary">
                <i class="fa-solid fa-plus me-2"></i> Hozzáadás
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- User ↔ Role list -->
    <div class="card border-primary-subtle shadow-sm mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="fa-solid fa-user-tag me-1"></i> Felhasználó ↔ Szerep
        </h5>
        <span class="text-body-secondary small">Összesen: <?= (int)$ur['total'] ?></span>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:90px;"><?= $sortHead('ur','user_id','User ID') ?></th>
                <th><?= $sortHead('ur','user_name','Név') ?></th>
                <th><?= $sortHead('ur','user_email','E-mail') ?></th>
                <th><?= $sortHead('ur','role_name','Szerep neve') ?></th>
                <th><?= $sortHead('ur','role_label','Szerep címke') ?></th>
                <th style="width:80px;"></th>
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
                <?php foreach ($userRoles as $urRow): ?>
                  <tr>
                    <td><code><?= (int)($urRow['user_id'] ?? 0) ?></code></td>
                    <td><?= e((string)($urRow['user_name'] ?? '')) ?></td>
                    <td><a href="mailto:<?= e((string)($urRow['user_email'] ?? '')) ?>"><?= e((string)($urRow['user_email'] ?? '')) ?></a></td>
                    <td><span class="fw-semibold"><?= e((string)($urRow['role_name'] ?? '')) ?></span></td>
                    <td><?= e((string)($urRow['role_label'] ?? '')) ?></td>
                    <td class="text-end">
                      <form method="post" action="<?= base_url('/rbac/assignments/detach') ?>" onsubmit="return confirm('Detach this role from the user?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="user_role">
                        <input type="hidden" name="user_id" value="<?= (int)($urRow['user_id'] ?? 0) ?>">
                        <input type="hidden" name="role_id" value="<?= (int)($urRow['role_id'] ?? 0) ?>">
                        <button class="btn btn-sm btn-danger" title="Detach">
                          <i class="fa-solid fa-xmark"></i>
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

      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <span class="text-body-secondary small"><?= e($rangeText($ur)) ?></span>
          <form method="get" class="m-0">
            <?php foreach ($_GET as $k => $v): if (!in_array($k, ['ur_per','ur_page'])): ?>
              <input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$v) ?>">
            <?php endif; endforeach; ?>
            <label class="small text-body-secondary me-1">Megjelenítés:</label>
            <select name="ur_per" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
              <?php foreach ([10,25,50,100] as $opt): ?>
                <option value="<?= $opt ?>" <?= $opt == $ur['per'] ? 'selected' : '' ?>><?= $opt ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>
        <div class="btn-group">
          <a class="btn btn-sm btn-secondary <?= $ur['page'] <= 1 ? 'disabled' : '' ?>"
             href="<?= $ur['page'] <= 1 ? '#' : e($buildUrl(['ur_page'=>$ur['page']-1,'ur_per'=>$ur['per']])) ?>">
            ‹ Előző
          </a>
          <a class="btn btn-sm btn-secondary <?= $ur['page'] >= $ur['pages'] ? 'disabled' : '' ?>"
             href="<?= $ur['page'] >= $ur['pages'] ? '#' : e($buildUrl(['ur_page'=>$ur['page']+1,'ur_per'=>$ur['per']])) ?>">
            Következő ›
          </a>
        </div>
      </div>
    </div>

    <!-- Role ↔ Permission list -->
    <div class="card border-secondary-subtle shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="fa-solid fa-diagram-project me-1"></i> Szerep ↔ Jogosultság
        </h5>
        <span class="text-body-secondary small">Összesen: <?= (int)$rp['total'] ?></span>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:90px;"><?= $sortHead('rp','role_id','Role ID') ?></th>
                <th><?= $sortHead('rp','role_name','Szerep neve') ?></th>
                <th><?= $sortHead('rp','role_label','Szerep címke') ?></th>
                <th><?= $sortHead('rp','perm_name','Jogosultság neve') ?></th>
                <th><?= $sortHead('rp','perm_label','Jogosultság címke') ?></th>
                <th style="width:80px;"></th>
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
                <?php foreach ($rolePerms as $rpRow): ?>
                  <tr>
                    <td><code><?= (int)($rpRow['role_id'] ?? 0) ?></code></td>
                    <td><span class="fw-semibold"><?= e((string)($rpRow['role_name'] ?? '')) ?></span></td>
                    <td><?= e((string)($rpRow['role_label'] ?? '')) ?></td>
                    <td><span class="fw-semibold"><?= e((string)($rpRow['perm_name'] ?? '')) ?></span></td>
                    <td><?= e((string)($rpRow['perm_label'] ?? '')) ?></td>
                    <td class="text-end">
                      <form method="post" action="<?= base_url('/rbac/assignments/detach') ?>" onsubmit="return confirm('Detach this permission from the role?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="role_perm">
                        <input type="hidden" name="role_id" value="<?= (int)($rpRow['role_id'] ?? 0) ?>">
                        <input type="hidden" name="permission_id" value="<?= (int)($rpRow['permission_id'] ?? 0) ?>">
                        <button class="btn btn-sm btn-danger" title="Detach">
                          <i class="fa-solid fa-xmark"></i>
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

      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <span class="text-body-secondary small"><?= e($rangeText($rp)) ?></span>
          <form method="get" class="m-0">
            <?php foreach ($_GET as $k => $v): if (!in_array($k, ['rp_per','rp_page'])): ?>
              <input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$v) ?>">
            <?php endif; endforeach; ?>
            <label class="small text-body-secondary me-1">Megjelenítés:</label>
            <select name="rp_per" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
              <?php foreach ([10,25,50,100] as $opt): ?>
                <option value="<?= $opt ?>" <?= $opt == $rp['per'] ? 'selected' : '' ?>><?= $opt ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>
        <div class="btn-group">
          <a class="btn btn-sm btn-secondary <?= $rp['page'] <= 1 ? 'disabled' : '' ?>"
             href="<?= $rp['page'] <= 1 ? '#' : e($buildUrl(['rp_page'=>$rp['page']-1,'rp_per'=>$rp['per']])) ?>">
            ‹ Előző
          </a>
          <a class="btn btn-sm btn-secondary <?= $rp['page'] >= $rp['pages'] ? 'disabled' : '' ?>"
             href="<?= $rp['page'] >= $rp['pages'] ? '#' : e($buildUrl(['rp_page'=>$rp['page']+1,'rp_per'=>$rp['per']])) ?>">
            Következő ›
          </a>
        </div>
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->

<!-- Choices.js (searchable selects) -->
<!-- !!!Minifie CSS from normal -->
<link rel="stylesheet" href="<?= base_url('public/assets/css/choices.css') ?>">
<script src="<?= base_url('public/assets/js/choices.min.js') ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.js-choices').forEach(function (el) {
    new Choices(el, {
      shouldSort: false,
      removeItemButton: false,
      searchPlaceholderValue: 'Keresés...',
      placeholder: true,
      placeholderValue: '– válassz –'
    });
  });
});
</script>
