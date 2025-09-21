<?php
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

/**
 * Build dynamic menu from per-app manifests.
 * - Parent active if its own regex matches OR any child is active.
 * - Child icon fallback: 'fa-regular fa-circle' if not provided.
 */
$items = \Core\MenuLoader::load();

$matches = function (?array $rxs, string $u): bool {
    if (!$rxs) return false;
    foreach ($rxs as $rx) {
        if (@preg_match($rx, $u)) return true;
    }
    return false;
};

$childActive = function (array $item) use ($matches, $uri): bool {
    foreach ($item['children'] ?? [] as $c) {
        if ($matches($c['match'] ?? [], $uri)) return true;
    }
    return false;
};
?>
<!--begin::Sidebar-->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <!--begin::Sidebar Brand-->
  <div class="sidebar-brand">
    <a href="<?= base_url('/') ?>" class="brand-link">
      <img src="<?= base_url('public/assets/adminlte/img/AdminLTELogo.png') ?>" alt="AdminLTE Logo" class="brand-image opacity-75 shadow">
      <span class="brand-text fw-light">AdminLTE 4</span>
    </a>
  </div>
  <!--end::Sidebar Brand-->

  <!--begin::Sidebar Wrapper-->
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <!--begin::Sidebar Menu-->
      <ul
        class="nav sidebar-menu flex-column"
        data-lte-toggle="treeview"
        role="navigation"
        aria-label="Main navigation"
        data-accordion="false"
        id="navigation"
      >
              <?php foreach ($items as $it):
              $hasChildren   = !empty($it['children']);
              $isSelfActive  = $matches($it['match'] ?? [], $uri);
              $hasActiveChild= $childActive($it);
              $activeClass   = ($isSelfActive || $hasActiveChild) ? 'active' : '';
              $openClass     = ($hasChildren && ($isSelfActive || $hasActiveChild)) ? 'menu-open' : '';
              $parentIcon    = $it['icon'] ?? 'fa-regular fa-folder';

              // If has children, parent is a toggler (no navigation); else it's a normal link
              $parentHref    = $hasChildren ? '#' : ($it['url'] ?? base_url($it['prefix'] ?? '/'));
            ?>
            <li class="nav-item <?= $openClass ?>">
              <a href="<?= htmlspecialchars($parentHref) ?>" class="nav-link <?= $activeClass ?>">
                <i class="nav-icon <?= htmlspecialchars($parentIcon) ?>"></i>
                <p>
                  <?= htmlspecialchars($it['label'] ?? 'Menu') ?>
                  <?php if ($hasChildren): ?>
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  <?php endif; ?>
                </p>
              </a>

            <?php if ($hasChildren): ?>
              <ul class="nav nav-treeview">
                <?php foreach ($it['children'] as $c):
                  $cActive = $matches($c['match'] ?? [], $uri) ? 'active' : '';
                  $cIcon   = $c['icon'] ?? 'fa-regular fa-circle';
                ?>
                  <li class="nav-item">
                    <a href="<?= htmlspecialchars($c['url']) ?>" class="nav-link <?= $cActive ?>">
                      <i class="nav-icon <?= htmlspecialchars($cIcon) ?>"></i>
                      <p><?= htmlspecialchars($c['label']) ?></p>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>

        <!-- (Optional) Static sections can remain below if you still needed -->
        <!-- <li class="nav-header">DOCUMENTATIONS</li> ... -->
      </ul>
      <!--end::Sidebar Menu-->
    </nav>
  </div>
  <!--end::Sidebar Wrapper-->
</aside>
<!--end::Sidebar-->
