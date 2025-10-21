<?php /** @var string $title */ ?>
<div class="app-content-header">
  <div class="container-fluid">
    <h3 class="mb-3"><?= htmlspecialchars($title) ?></h3>
  </div>
</div>

<div class="container-fluid">
  <div class="card card-primary">
    <div class="card-header">
      <h3 class="card-title">Család adatai</h3>
    </div>
    <form method="POST" action="<?= base_url('/adatlap/family/store') ?>">
      <div class="card-body">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label for="family_name" class="form-label">Család neve</label>
          <input type="text" class="form-control" id="family_name" name="family_name" required>
        </div>
      </div>
      <div class="card-footer">
        <button type="submit" class="btn btn-success">Mentés</button>
        <a href="<?= base_url('/adatlap/family') ?>" class="btn btn-secondary">Mégse</a>
      </div>
    </form>
  </div>
</div>
