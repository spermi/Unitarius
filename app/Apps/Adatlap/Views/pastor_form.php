<?php
/** @var string $title */
/** @var array<string,mixed> $pastor */

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
$pastorUuid = $pastor['uuid'] ?? '';
?>
<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <h3 class="mb-0">
      <i class="fa-solid fa-user-tie me-2"></i><?= e($title ?? 'Lelkész adatlap szerkesztése') ?>
      <?php if (!empty($pastor['last_name']) || !empty($pastor['first_name'])): ?>
        <small class="text-muted">— <?= e(trim(($pastor['last_name'] ?? '') . ' ' . ($pastor['first_name'] ?? ''))) ?></small>
      <?php endif; ?>
    </h3>
    <div class="btn-group mt-2 mt-sm-0">
      <a href="<?= base_url('/adatlap/pastor/' . e($pastorUuid)) ?>" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Vissza
      </a>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">

    <div class="card border-primary-subtle mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="fa-solid fa-id-card-clip me-2"></i>Személyes adatok</h5>
      </div>
      <div class="card-body">
        <form method="POST" action="<?= base_url('/adatlap/pastor/' . e($pastorUuid) . '/update') ?>">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-4">
              <label for="last_name" class="form-label">Vezetéknév</label>
              <input type="text" class="form-control" id="last_name" name="last_name" value="<?= e($pastor['last_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label for="first_name" class="form-label">Keresztnév</label>
              <input type="text" class="form-control" id="first_name" name="first_name" value="<?= e($pastor['first_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label for="birth_name" class="form-label">Születési név</label>
              <input type="text" class="form-control" id="birth_name" name="birth_name" value="<?= e($pastor['birth_name'] ?? '') ?>">
            </div>

            <div class="col-md-4">
              <label for="birth_date" class="form-label">Születési dátum</label>
              <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?= e($pastor['birth_date'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label for="birth_place" class="form-label">Születési hely</label>
              <input type="text" class="form-control" id="birth_place" name="birth_place" value="<?= e($pastor['birth_place'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label for="notes" class="form-label">Megjegyzés</label>
              <textarea class="form-control" id="notes" name="notes" rows="1"><?= e($pastor['notes'] ?? '') ?></textarea>
            </div>

            <div class="col-md-4">
              <label for="ordination_date" class="form-label">Felszentelés dátuma</label>
              <input type="date" class="form-control" id="ordination_date" name="ordination_date" value="<?= e($pastor['ordination_date'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label for="ordination_place" class="form-label">Felszentelés helye</label>
              <input type="text" class="form-control" id="ordination_place" name="ordination_place" value="<?= e($pastor['ordination_place'] ?? '') ?>">
            </div>
          </div>
          <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-save me-1"></i> Mentés
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->

<!-- Magyar dátumformátum megjelenítés (YYYY.MM.DD) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const dateInputs = document.querySelectorAll('input[type="date"]');

  dateInputs.forEach(input => {
    input.placeholder = 'ÉÉÉÉ.HH.NN';

    if (input.value) updateDisplay(input);

    input.addEventListener('change', e => {
      updateDisplay(e.target);
    });

    input.addEventListener('blur', e => {
      const val = e.target.value.trim();
      if (/^\d{4}\.\d{2}\.\d{2}$/.test(val)) {
        const [y, m, d] = val.split('.');
        e.target.value = `${y}-${m}-${d}`;
        updateDisplay(e.target);
      }
    });
  });

  function updateDisplay(input) {
    const val = input.value;
    if (val && /^\d{4}-\d{2}-\d{2}$/.test(val)) {
      const [y, m, d] = val.split('-');
      input.setAttribute('data-display', `${y}.${m}.${d}`);
    } else {
      input.removeAttribute('data-display');
    }
  }
});
</script>