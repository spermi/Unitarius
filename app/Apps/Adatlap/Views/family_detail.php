<?php
/** @var array $family */
/** @var array $members */
/** @var array|null $pastor */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Detect existing husband/wife in this family (based on relation_code)
$hasHusband = false;
$hasWife    = false;

// Split members visually: parents left/right + children below
$husbandMembers  = [];
$wifeMembers     = [];
$childrenMembers = [];

foreach ($members as $m) {
    $code = strtolower((string)($m['relation_code'] ?? ''));
    if (in_array($code, ['ferj','husband'], true)) {
        $hasHusband = true;
        $husbandMembers[] = $m;
    } elseif (in_array($code, ['feleseg','wife'], true)) {
        $hasWife = true;
        $wifeMembers[] = $m;
    } elseif (in_array($code, ['gyermek','child','children'], true)) {
        $childrenMembers[] = $m;
    }
}
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <h3 class="mb-0"><i class="fa-solid fa-people-group me-2"></i><?= e($family['family_name'] ?? 'Család') ?></h3>
    <div class="btn-group mt-2 mt-sm-0">
      <a href="<?= base_url('/adatlap/family') ?>" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Vissza
      </a>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMemberModal">
        <i class="fa-solid fa-user-plus me-1"></i> Új családtag
      </button>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">

    <!-- NEW: Visual split – Parents (left/right) + Children (below) -->
    <div class="card mb-4 border-primary-subtle">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="fa-solid fa-sitemap me-1"></i> Család elrendezés</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
        
        <!-- Left parent (Husband) -->
          <div class="col-12 col-lg-6">
            <div class="border rounded p-3 h-100">
              <div class="d-flex align-items-center mb-2">
                <i class="fa-solid fa-person me-2 text-primary"></i>
                <strong>
                  <?php if (!empty($husbandMembers)): ?>
                    <?= e($husbandMembers[0]['name'] ?? '-') ?> — Férj
                  <?php else: ?>
                    Szülő (Férj)
                  <?php endif; ?>
                </strong>
              </div>
              <?php if (!empty($husbandMembers)): ?>
                <ul class="list-group list-group-flush">
                  <?php foreach ($husbandMembers as $h): ?>
                    <li class="list-group-item px-0">
                      <div class="fw-semibold"><?= e($h['name'] ?? '-') ?></div>  
                      <div class="text-muted small">
                        <?= e($h['relation_label'] ?? 'Férj') ?>
                        <?php if (!empty($h['birth_date'])): ?>
                          • <?= e(format_date_hu($h['birth_date'])) ?>
                        <?php endif; ?>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <div class="text-muted fst-italic">Nincs megadva.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Right parent (Wife) -->
          <div class="col-12 col-lg-6">
            <div class="border rounded p-3 h-100">
              <div class="d-flex align-items-center mb-2">
                <i class="fa-solid fa-person-dress me-2 text-danger"></i>
                <strong>
                  <?php if (!empty($wifeMembers)): ?>
                    <?= e($wifeMembers[0]['name'] ?? '-') ?> — Feleség
                  <?php else: ?>
                    Szülő (Feleség)
                  <?php endif; ?>
                </strong>
              </div>
              <?php if (!empty($wifeMembers)): ?>
                <ul class="list-group list-group-flush">
                  <?php foreach ($wifeMembers as $w): ?>
                    <li class="list-group-item px-0">
                      <div class="fw-semibold"><?= e($w['name'] ?? '-') ?></div>
                      <div class="text-muted small">
                        <?= e($w['relation_label'] ?? 'Feleség') ?>
                        <?php if (!empty($w['birth_date'])): ?>
                          • <?= e(format_date_hu($w['birth_date'])) ?>
                        <?php endif; ?>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <div class="text-muted fst-italic">Nincs megadva.</div>
              <?php endif; ?>
            </div>
          </div>


          <!-- Children row -->
          <div class="col-12">
            <div class="border rounded p-3">
              <div class="d-flex align-items-center mb-2">
                <i class="fa-solid fa-children me-2 text-success"></i>
                <strong>Gyermekek</strong>
              </div>
              <?php if (!empty($childrenMembers)): ?>
                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Név</th>
                        <th>Született</th>
                        <th>Szülők (apa • anya)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($childrenMembers as $c): ?>
                        <tr>
                          <td class="fw-semibold"><?= e($c['name'] ?? '-') ?></td>
                          <td><?= e(!empty($c['birth_date']) ? format_date_hu($c['birth_date']) : '-') ?></td>
                          <td class="text-muted small">
                            <?= e($c['father_name'] ?? ($hasHusband ? 'apa' : '-')) ?> •
                            <?= e($c['mother_name'] ?? ($hasWife    ? 'anya' : '-')) ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="text-muted fst-italic">Nincs gyermek megadva.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

   
    <!-- Add member modal -->
    <div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <form method="POST" action="<?= base_url('/adatlap/family/member/save') ?>">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title"><i class="fa-solid fa-user-plus me-1"></i> Új családtag hozzáadása</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <?= csrf_field() ?>
              <input type="hidden" name="family_uuid" value="<?= e($family['uuid']) ?>">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Név</label>
                  <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Kapcsolat</label>
                  <select name="relation_code" class="form-select" required>
                    <option value="">-- válassz --</option>
                    <?php foreach ($relations as $r): ?>
                      <option value="<?= e($r['code']) ?>"><?= e($r['label_hu']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Születési dátum</label>
                  <input type="date" name="birth_date" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Elhalálozás dátuma</label>
                  <input type="date" name="death_date" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label d-block">Nem</label>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="gender" id="genderMale" value="male">
                    <label class="form-check-label" for="genderMale">Férfi</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="female">
                    <label class="form-check-label" for="genderFemale">Nő</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="gender" id="genderNone" value="" checked>
                    <label class="form-check-label" for="genderNone">Nincs megadva</label>
                  </div>
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

    <!-- Family tree visual -->
    <div class="card border-primary-subtle mt-4">
      <div class="card-header" >
        <h5 class="mb-0"><i class="fa-solid fa-diagram-project me-1"></i> Családfa vizualizáció</h5>
      </div>
      <div class="card-body">
        <div id="familyTree" style="min-height:400px;"></div>
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Relation code sets – igazítsd a saját kódjaidhoz, ha eltérnek
  const SPOUSE_MALE   = ['ferj','husband'];
  const SPOUSE_FEMALE = ['feleseg','wife'];
  const CHILDREN      = ['gyermek','child'];

  const hasHusband = <?= $hasHusband ? 'true' : 'false' ?>;
  const hasWife    = <?= $hasWife    ? 'true' : 'false'  ?>;

  const relSelect    = document.querySelector('select[name="relation_code"]');
  const genderMale   = document.getElementById('genderMale');
  const genderFemale = document.getElementById('genderFemale');
  const genderNone   = document.getElementById('genderNone');

  if (!relSelect) return;

  function setOptionVisibility(selectEl, value, visible) {
    const opt = Array.from(selectEl.options).find(o => (o.value || '').toLowerCase() === value);
    if (!opt) return;
    opt.hidden = !visible;
    opt.disabled = !visible;
  }

  function firstVisibleChildCode() {
    const opt = Array.from(relSelect.options).find(o => {
      const v = (o.value || '').toLowerCase();
      return v && CHILDREN.includes(v) && !o.hidden && !o.disabled;
    });
    return opt ? opt.value : '';
  }

  function ensureChildSelectedIfNeeded() {
    if (hasHusband && hasWife) {
      const childVal = firstVisibleChildCode();
      if (childVal) {
        relSelect.value = childVal;
        if (genderNone) genderNone.checked = true;
        relSelect.dispatchEvent(new Event('change', {bubbles:true}));
      }
    }
  }

  function applyFiltering() {
    const allCodes = Array.from(relSelect.options).map(o => (o.value || '').toLowerCase());
    allCodes.forEach(c => { if (c) setOptionVisibility(relSelect, c, true); });

    if (hasHusband && hasWife) {
      allCodes.forEach(c => { if (!CHILDREN.includes(c)) setOptionVisibility(relSelect, c, false); });
      ensureChildSelectedIfNeeded();
      return;
    }

    if (hasHusband) { SPOUSE_MALE.forEach(c => setOptionVisibility(relSelect, c, false)); }
    if (hasWife)    { SPOUSE_FEMALE.forEach(c => setOptionVisibility(relSelect, c, false)); }
  }

  function applyGenderAutoSelect(code) {
    const c = (code || '').toLowerCase();
    if (SPOUSE_MALE.includes(c)) {
      if (genderMale)   genderMale.checked = true;
    } else if (SPOUSE_FEMALE.includes(c)) {
      if (genderFemale) genderFemale.checked = true;
    } else {
      if (genderNone) genderNone.checked = true;
    }
  }

  applyFiltering();

  relSelect.addEventListener('change', function () {
    applyGenderAutoSelect(this.value);
  });

  const modal = document.getElementById('addMemberModal');
  if (modal) {
    modal.addEventListener('shown.bs.modal', function () {
      if (hasHusband && hasWife) ensureChildSelectedIfNeeded();
    });
  }
});
</script>

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
    if (val && /^\d{4}-\d{2}-\d2$/.test(val)) { // if you had a helper, keep as-is
      const [y, m, d] = val.split('-');
      input.setAttribute('data-display', `${y}.${m}.${d}`);
    } else {
      input.removeAttribute('data-display');
    }
  }
});
</script>
