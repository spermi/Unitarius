<?php
/** @var string $title */
/** @var array<string,mixed> $pastor */
/** @var array<int,array<string,mixed>> $education */

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
$pastorUuid = $pastor['uuid'] ?? '';
?>
<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <h3 class="mb-0">
      <i class="fa-solid fa-user-tie me-2"></i><?= e($title ?? 'Lelkész adatlap') ?>
      <?php if (!empty($pastor['last_name']) || !empty($pastor['first_name'])): ?>
        <small class="text-muted">— <?= e(trim(($pastor['last_name'] ?? '') . ' ' . ($pastor['first_name'] ?? ''))) ?></small>
      <?php endif; ?>
    </h3>
    <div class="btn-group mt-2 mt-sm-0">
      <a href="<?= base_url('/adatlap') ?>" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Vissza
      </a>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEducationModal">
        <i class="fa-solid fa-plus me-1"></i> Új tanulmány
      </button>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">

    <!-- Pastor basic info (read-only) -->
    <div class="card border-primary-subtle mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="fa-solid fa-id-card-clip me-2"></i>Személyes adatok</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label text-muted">Vezetéknév</label>
            <div class="form-control-plaintext fw-semibold"><?= e($pastor['last_name'] ?? '—') ?></div>
          </div>
          <div class="col-md-4">
            <label class="form-label text-muted">Keresztnév</label>
            <div class="form-control-plaintext fw-semibold"><?= e($pastor['first_name'] ?? '—') ?></div>
          </div>
          <div class="col-md-4">
            <label class="form-label text-muted">Születési név</label>
            <div class="form-control-plaintext"><?= e($pastor['birth_name'] ?? '—') ?></div>
          </div>

          <div class="col-md-4">
            <label class="form-label text-muted">Születési dátum</label>
            <div class="form-control-plaintext"><?= e($pastor['birth_date'] ?? '—') ?></div>
          </div>
          <div class="col-md-4">
            <label class="form-label text-muted">Születési hely</label>
            <div class="form-control-plaintext"><?= e($pastor['birth_place'] ?? '—') ?></div>
          </div>
          <div class="col-md-4">
            <label class="form-label text-muted">Megjegyzés</label>
            <div class="form-control-plaintext"><?= e($pastor['notes'] ?? '—') ?></div>
          </div>

          <div class="col-md-4">
            <label class="form-label text-muted">Felszentelés dátuma</label>
            <div class="form-control-plaintext"><?= e($pastor['ordination_date'] ?? '—') ?></div>
          </div>
          <div class="col-md-4">
            <label class="form-label text-muted">Felszentelés helye</label>
            <div class="form-control-plaintext"><?= e($pastor['ordination_place'] ?? '—') ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Education list -->
    <div class="card border-primary-subtle">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="fa-solid fa-graduation-cap me-2"></i>Tanulmányok</h5>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:34%;">Intézmény</th>
              <th style="width:22%;">Szak / terület</th>
              <th style="width:14%;">Fokozat</th>
              <th style="width:18%;">Időszak</th>
              <th style="width:12%;" class="text-end">—</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($education)): ?>
              <tr><td colspan="5" class="text-center text-muted">Még nincs felvitt tanulmány.</td></tr>
            <?php else: ?>
              <?php foreach ($education as $row): ?>
                <tr>
                  <td><?= e($row['institution'] ?? '—') ?></td>
                  <td><?= e($row['field_of_study'] ?? '—') ?></td>
                  <td><?= e($row['degree'] ?? '—') ?></td>
                  <td>
                    <?php
                      $sd = trim((string)($row['start_date'] ?? ''));
                      $ed = trim((string)($row['end_date'] ?? ''));
                      echo e($sd !== '' ? $sd : '—');
                      echo ' – ';
                      echo e($ed !== '' ? $ed : '…');
                    ?>
                  </td>
                  <td class="text-end">
                    <!-- Placeholder for future edit/delete -->
                    <span class="text-muted">—</span>
                  </td>
                </tr>
                <?php if (!empty($row['note'])): ?>
                  <tr>
                    <td colspan="5" class="text-muted small">
                      <i class="fa-regular fa-note-sticky me-1"></i><?= e((string)$row['note']) ?>
                    </td>
                  </tr>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->

<!-- ÚJ TANULMÁNY MODÁL -->
<div class="modal fade" id="addEducationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="<?= base_url('/adatlap/pastor/' . e($pastorUuid) . '/education/store') ?>">
        <?= csrf_field() ?>
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="fa-solid fa-plus me-1"></i> Új tanulmány hozzáadása</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Intézmény</label>
              <input type="text" name="institution" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Szak / tanulmányi terület</label>
              <input type="text" name="field_of_study" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Fokozat</label>
              <input type="text" name="degree" class="form-control" placeholder="pl. BA / MA / ThD">
            </div>
            <div class="col-md-4">
              <label class="form-label">Kezdés</label>
              <input type="date" name="start_date" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Befejezés</label>
              <input type="date" name="end_date" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Megjegyzés</label>
              <textarea name="note" class="form-control" rows="2"></textarea>
            </div>
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
