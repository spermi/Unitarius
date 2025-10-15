<?php
/** @var string $title */
/** @var array<string,mixed>|null $studies */

function e(string $v): string {
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h3 class="mb-0"><?= e($title ?? 'Tanulmányok') ?></h3>
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
        <h3 class="card-title"><i class="fa-solid fa-graduation-cap"></i> Tanulmányok</h3>
      </div>

      <div class="card-body">
        <form method="post" action="<?= base_url('/adatlap/studies/save') ?>">
          <?= csrf_field() ?>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Az érettségi vizsga éve</label>
              <input type="text" name="erettsegi_ev" class="form-control" value="<?= e($studies['erettsegi_ev'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tanintézet</label>
              <input type="text" name="erettsegi_intezmeny" class="form-control" value="<?= e($studies['erettsegi_intezmeny'] ?? '') ?>">
            </div>
          </div>

          <hr>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Teológiai tanintézet</label>
              <input type="text" name="teologia_intezmeny" class="form-control" value="<?= e($studies['teologia_intezmeny'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Kezdés éve</label>
              <input type="text" name="kezdes_ev" class="form-control" value="<?= e($studies['kezdes_ev'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Végzés éve</label>
              <input type="text" name="vegzes_ev" class="form-control" value="<?= e($studies['vegzes_ev'] ?? '') ?>">
            </div>
          </div>

          <hr>

          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label">Teológiai szakvizsga éve</label>
              <input type="text" name="szakvizsga_ev" class="form-control" value="<?= e($studies['szakvizsga_ev'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Licenciátusi vizsga éve</label>
              <input type="text" name="licenc_ev" class="form-control" value="<?= e($studies['licenc_ev'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Magiszteri vizsga éve</label>
              <input type="text" name="magiszter_ev" class="form-control" value="<?= e($studies['magiszter_ev'] ?? '') ?>">
            </div>
          </div>

          <hr>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Lelkészképesítő záróvizsga éve</label>
              <input type="text" name="kepesito_ev" class="form-control" value="<?= e($studies['kepesito_ev'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Felszentelés éve és helyszíne</label>
              <div class="input-group">
                <input type="text" name="felszent_ev" class="form-control" placeholder="Év" value="<?= e($studies['felszent_ev'] ?? '') ?>">
                <input type="text" name="felszent_hely" class="form-control" placeholder="Helyszín" value="<?= e($studies['felszent_hely'] ?? '') ?>">
              </div>
            </div>
          </div>

          <div class="text-end">
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-save"></i> Mentés
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>
<!--end::App Content-->
