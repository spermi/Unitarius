<?php
/** @var array $people */
/** @var string $title */
?>
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1><?= htmlspecialchars($title) ?></h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Vezérlőpult</a></li>
          <li class="breadcrumb-item active">Személyek</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Persons</h3></div>
      <div class="card-body table-responsive">
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>#</th>
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
          <?php foreach ($people as $p): ?>
            <tr>
              <td><?= (int)$p['person_id'] ?></td>
              <td><?= htmlspecialchars($p['fullname'] ?? '') ?></td>
              <td><?= htmlspecialchars($p['given_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($p['middle_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($p['family_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($p['birth_date'] ?? '') ?></td>
              <td><?= htmlspecialchars($p['gender'] ?? '') ?></td>
              <td><?= htmlspecialchars($p['status'] ?? '') ?></td>
              <td>
                <?php
                  $ts = strtotime((string)($p['created_at'] ?? ''));
                  echo $ts ? date('Y-m-d H:i', $ts) : htmlspecialchars((string)$p['created_at']);
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
