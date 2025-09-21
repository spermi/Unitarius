<?php
// Plain view: NINCS $this->extend()/section()
// A View::render valószínűleg beágyazza ezt a tartalmat a layoutba ($content-ként).
?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Testcard </h3>
  </div>
  <div class="card-body">
    <p>Szia <?= htmlspecialchars($name ?? 'Világ') ?>! 🎉</p>
    <p>Ez már AdminLTE stílusban jelenik meg.</p>
    <button class="btn btn-primary"><i class="fa fa-check"></i> Teszt gomb</button>
  </div>
</div>
