<?php
/** @var array $families */
/** @var string $title */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <h3 class="mb-0"><i class="fa-solid fa-people-group me-2"></i><?= e($title ?? 'Családok') ?></h3>
    <div class="btn-group mt-2 mt-sm-0">
      <a href="<?= base_url('/adatlap') ?>" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Vissza az adatlaphoz
      </a>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFamilyModal">
        <i class="fa-solid fa-plus me-1"></i> Új család
      </button>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">

    <div class="card border-primary-subtle">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="fa-solid fa-people-roof me-1"></i> Családlista</h5>
      </div>

      <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
       <thead class="table-light">
        <tr>
          <th class="text-center" style="width:50px;">#</th>
          <th>Családnév</th>
          <th>Létrehozva</th>
          <th class="text-center" style="width:100px;">Aktuális</th> <!-- NEW -->
          <th class="text-end" style="width:120px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($families)): ?>
          <tr><td colspan="5" class="text-center text-muted">Nincsenek családok az adatbázisban.</td></tr>
        <?php else: ?>
          <?php foreach ($families as $i => $f): ?>
            <tr<?= !empty($f['is_current']) ? ' class="table-primary-subtle"' : '' ?>>
              <td class="text-center"><?= $i + 1 ?></td>
              <td><?= e($f['family_name'] ?? '') ?></td>
              <td><?= e($f['created_at'] ?? '') ?></td>
              <td class="text-center">
                <?php if (!empty($f['is_current'])): ?>
                  <span class="badge bg-primary">Igen</span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <a href="<?= base_url('/adatlap/family/' . $f['uuid']) ?>" class="btn btn-sm btn-primary">
                  <i class="fa-solid fa-users me-1"></i> Megnyitás
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
        </table>
      </div>
    </div>

    <!-- ÚJ CSALÁD MODÁL -->
    <div class="modal fade" id="addFamilyModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form method="POST" action="<?= base_url('/adatlap/family/store') ?>">
            <?= csrf_field() ?>
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title"><i class="fa-solid fa-plus me-1"></i> Új család létrehozása</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Családnév</label>
                <input type="text" name="family_name" class="form-control" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Mégse</button>
              <button type="submit" class="btn btn-primary">Mentés</button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->


