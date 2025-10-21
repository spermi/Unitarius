<?php
/** @var array $family */
/** @var array $members */
/** @var array|null $pastor */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
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

    <!-- Pastor info -->
    <div class="alert alert-light border d-flex align-items-center mb-4">
      <i class="fa-solid fa-church me-2 text-primary"></i>
      <div>
        <strong>Lelkipásztor:</strong>
        <?= $pastor ? e($pastor['full_name']) : '<em>nincs kapcsolt pásztor</em>' ?>
      </div>
    </div>

    <!-- Members table -->
    <div class="card mb-4 border-primary-subtle">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="fa-solid fa-users me-1"></i> Családtagok</h5>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Név</th>
              <th>Kapcsolat</th>
              <th>Született</th>
              <th>Elhunyt</th>
              <th>Szülő</th>
              <th>Elsődleges</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($members)): ?>
              <tr><td colspan="6" class="text-center text-muted">Nincsenek családtagok.</td></tr>
            <?php else: ?>
              <?php foreach ($members as $m): ?>
                <tr>
                  <td><?= e($m['name']) ?></td>
                  <td><?= e($m['relation']) ?></td>
                  <td><?= e(format_date_hu($m['birth_date'] ?? '')) ?></td>
                  <td><?= e(format_date_hu($m['death_date'] ?? '')) ?></td>
                  <td><?= e($m['parent_uuid'] ?? '-') ?></td>
                  <td><?= !empty($m['is_primary']) ? '<i class="fa-solid fa-check text-success"></i>' : '' ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
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
              <select name="relation" class="form-select" required>
                <option value="">-- válassz --</option>
                <option value="férj">Férj</option>
                <option value="feleség">Feleség</option>
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
            <!-- Szülő UUID egyelőre rejtve -->
            <input type="hidden" name="parent_uuid" value="">
            <div class="col-md-6 d-flex align-items-end">
              <div class="form-check">
                <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="isPrimary">
                <label for="isPrimary" class="form-check-label">Elsődleges családtag</label>
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

<!-- D3.js CDN -->
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
function renderTree(data) {
  const treeData = data.root_member;
  const width = document.getElementById("familyTree").clientWidth;
  const dx = 20, dy = width / 6;
  const tree = d3.tree().nodeSize([dx, dy]);
  const diagonal = d3.linkHorizontal().x(d => d.y).y(d => d.x);
  const root = d3.hierarchy(treeData);
  root.x0 = dy / 2; root.y0 = 0; tree(root);

  const svg = d3.select("#familyTree").append("svg")
    .attr("viewBox", [-40, -20, width, 500])
    .style("font", "12px sans-serif");

  const g = svg.append("g").attr("transform", `translate(80,${dx})`);

  g.append("g").selectAll("path").data(root.links())
    .join("path").attr("fill", "none").attr("stroke", "#555")
    .attr("stroke-opacity", 0.4).attr("stroke-width", 1.5).attr("d", diagonal);

  const node = g.append("g").selectAll("g").data(root.descendants())
    .join("g").attr("transform", d => `translate(${d.y},${d.x})`);

  node.append("circle")
    .attr("r", 5)
    .attr("fill", d => d.data.is_primary ? "#0d6efd" : "#999")
    .attr("stroke", "#333").attr("stroke-width", 0.5)
    .append("title")
    .text(d => `${d.data.name}\n${d.data.relation || ''}`);

  node.append("text")
    .attr("dy", "-0.5em")
    .attr("x", d => d.children ? -8 : 8)
    .attr("text-anchor", d => d.children ? "end" : "start")
    .attr("font-weight", d => d.data.is_primary ? "700" : "400")
    .text(d => d.data.name)
    .clone(true).lower().attr("stroke", "white");

  node.append("text")
    .attr("dy", "1em")
    .attr("x", d => d.children ? -8 : 8)
    .attr("text-anchor", d => d.children ? "end" : "start")
    .attr("font-size", "10px")
    .attr("fill", "#555")
    .text(d => {
      let info = d.data.relation || '';
      if (d.data.birth_date) info += (info ? ' • ' : '') + d.data.birth_date;
      return info;
    });
}
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
    if (val && /^\d{4}-\d{2}-\d{2}$/.test(val)) {
      const [y, m, d] = val.split('-');
      input.setAttribute('data-display', `${y}.${m}.${d}`);
    } else {
      input.removeAttribute('data-display');
    }
  }
});
</script>

<style>
/* --- Magyar formátumú dátum megjelenítés --- */
input[type="date"] {
  position: relative;
  color: transparent; /* elrejtjük a natív ISO szöveget */
}

input[type="date"]::after {
  content: attr(data-display);
  position: absolute;
  left: 10px;
  top: 7px;
  color: var(--bs-body-color);
  pointer-events: none;
  font-size: 0.95em;
}

input[type="date"]:focus {
  color: var(--bs-body-color); /* fókuszban mutatja normálisan */
}
</style>
