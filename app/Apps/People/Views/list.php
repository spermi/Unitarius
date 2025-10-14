<?php
/** @var string $title */
/** @var array<int,array<string,mixed>> $people */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <h3 class="mb-0"><i class="fa-regular fa-user me-2"></i> <?= e($title ?? 'Személyek') ?></h3>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">

    <div class="card border-primary-subtle shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="fa-solid fa-users me-1"></i> Személyek listája
        </h5>
        <span class="text-body-secondary small">Összesen: <?= count($people) ?></span>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:60px;">#</th>
                <th>Név</th>
                <th>Given</th>
                <th>Middle</th>
                <th>Family</th>
                <th>Születési dátum</th>
                <th>Nem</th>
                <th>Státusz</th>
                <th>Létrehozva</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($people)): ?>
                <tr>
                  <td colspan="9" class="text-center p-4 text-secondary">
                    <i class="fa-regular fa-circle-xmark me-1"></i> Nincsenek rögzített személyek.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($people as $p): ?>
                  <tr>
                    <td><code><?= (int)($p['person_id'] ?? 0) ?></code></td>
                    <td><?= e((string)($p['fullname'] ?? '')) ?></td>
                    <td><?= e((string)($p['given_name'] ?? '')) ?></td>
                    <td><?= e((string)($p['middle_name'] ?? '')) ?></td>
                    <td><?= e((string)($p['family_name'] ?? '')) ?></td>
                    <td><?= e((string)($p['birth_date'] ?? '')) ?></td>
                    <td><?= e((string)($p['gender'] ?? '')) ?></td>
                    <td><?= e((string)($p['status'] ?? '')) ?></td>
                    <td class="text-nowrap">
                      <?php
                        $ts = strtotime((string)($p['created_at'] ?? ''));
                        echo $ts ? date('Y.m.d H:i', $ts) : e((string)($p['created_at'] ?? ''));
                      ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer text-body-secondary small">
        <i class="fa-solid fa-circle-info me-1"></i> A lista az összes elérhető személyt tartalmazza.
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->
