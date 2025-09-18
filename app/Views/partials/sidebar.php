<?php
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$active = function(string $path) use ($uri): string {
  if ($uri === $path) return 'active';
  if ($path !== '/' && str_starts_with($uri, rtrim($path,'/').'/')) return 'active';
  return '';
};
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="<?= base_url('/') ?>" class="brand-link">
    <span class="brand-text fw-light">Unitarius</span>
  </a>
  <div class="sidebar">
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
        <li class="nav-item">
          <a href="<?= base_url('/') ?>" class="nav-link <?= $active(parse_url(base_url('/'), PHP_URL_PATH) ?: '/') ?>">
            <i class="nav-icon fas fa-home"></i>
            <p>Kezdőlap</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('members') ?>" class="nav-link <?= $active(parse_url(base_url('members'), PHP_URL_PATH)) ?>">
            <i class="nav-icon fas fa-users"></i>
            <p>Tagok</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('units') ?>" class="nav-link <?= $active(parse_url(base_url('units'), PHP_URL_PATH)) ?>">
            <i class="nav-icon fas fa-building"></i>
            <p>Egységek</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= base_url('events') ?>" class="nav-link <?= $active(parse_url(base_url('events'), PHP_URL_PATH)) ?>">
            <i class="nav-icon fas fa-calendar"></i>
            <p>Események</p>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>
