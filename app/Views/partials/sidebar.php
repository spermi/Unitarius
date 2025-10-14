<?php
// --------------------------------------------------------------
// Normalize current URI against base path (APP_BASE_PATH / APP_URL)
// --------------------------------------------------------------
$rawUri  = $_SERVER['REQUEST_URI'] ?? '/';
$uriPath = parse_url($rawUri, PHP_URL_PATH) ?? '/';

// Prefer APP_BASE_PATH, else derive from APP_URL path
$baseSub = rtrim((string)($_ENV['APP_BASE_PATH'] ?? ''), '/');
if ($baseSub === '') {
    $baseFromUrl = parse_url($_ENV['APP_URL'] ?? '', PHP_URL_PATH);
    if (is_string($baseFromUrl) && $baseFromUrl !== '') {
        $baseSub = rtrim($baseFromUrl, '/'); // e.g. "/unitarius"
    }
}
if ($baseSub !== '' && str_starts_with($uriPath, $baseSub)) {
    $uriPath = substr($uriPath, strlen($baseSub)) ?: '/';
}
if ($uriPath !== '/') {
    $uriPath = rtrim($uriPath, '/');
    if ($uriPath === '') { $uriPath = '/'; }
}

// --------------------------------------------------------------
// Build dynamic menu from per-app manifests + RBAC filtering.
// Parent active if its own regex matches OR any child is active.
// --------------------------------------------------------------
$items = \Core\MenuLoader::load();

/**
 * RBAC-filter for menu items based on 'perm'.
 * - Parent kept if: (parent perm allowed) OR (has at least one allowed child).
 * - Children kept if: no 'perm' OR can($perm) is true.
 * - Structure preserved; only disallowed entries are removed.
 */
$filterMenuByRBAC = function(array $menu): array {
    $out = [];
    foreach ($menu as $item) {
        $parentAllowed = empty($item['perm']) || \can((string)$item['perm']);

        // filter children by perm
        $children = $item['children'] ?? [];
        $children = array_values(array_filter($children, function($c){
            return empty($c['perm']) || \can((string)$c['perm']);
        }));

        // keep parent if parent allowed OR there are visible children
        if ($parentAllowed || !empty($children)) {
            $item['children'] = $children;
            $out[] = $item;
        }
    }
    return $out;
};

$items = $filterMenuByRBAC($items);

// Safe matcher: returns true only if a valid regex matches exactly once
$matches = function (?array $rxs, string $u): bool {
    if (empty($rxs)) return false;
    foreach ($rxs as $rx) {
        if (@preg_match($rx, '') === false) continue;
        if (@preg_match($rx, $u) === 1) return true;
    }
    return false;
};

// Compute if any child matches
$childActive = function (array $item) use ($matches, $uriPath): bool {
    foreach ($item['children'] ?? [] as $c) {
        if ($matches($c['match'] ?? [], $uriPath)) return true;
    }
    return false;
};

// Optional: filter out parents that have no link and no visible children
$shouldRenderParent = function (array $item): bool {
    $hasChildren = !empty($item['children']);
    $hasUrl      = !empty($item['url']);
    return $hasUrl || $hasChildren;
};
?>
<!--begin::Sidebar-->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <!--begin::Sidebar Brand-->
  <div class="sidebar-brand">
    <a href="<?= base_url('/') ?>" class="brand-link">
      <img src="<?= base_url('public/assets/adminlte/img/AdminLTELogo.png') ?>" alt="AdminLTE Logo" class="brand-image opacity-75 shadow">
      <span class="brand-text fw-light">Unitárius Admin</span>
    </a>
  </div>
  <!--end::Sidebar Brand-->

  <!--begin::Sidebar Wrapper-->
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <!--begin::Sidebar Menu-->
      <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation" aria-label="Main navigation" data-accordion="false" id="navigation" >
          <?php foreach ($items as $it):
              // Skip empty parents (no url and no children)
              if (!$shouldRenderParent($it)) continue;

              $hasChildren    = !empty($it['children']);
              $isSelfActive   = $matches($it['match'] ?? [], $uriPath);
              $hasActiveChild = $childActive($it);

              $activeClass = ($isSelfActive || $hasActiveChild) ? 'active' : '';
              $openClass   = ($hasChildren && ($isSelfActive || $hasActiveChild)) ? 'menu-open' : '';

              $parentIcon  = $it['icon'] ?? 'fa-regular fa-folder';
              $parentHref  = $hasChildren ? '#' : ($it['url'] ?? base_url($it['prefix'] ?? '/'));
          ?>
          <li class="nav-item <?= $openClass ?>">
            <a href="<?= htmlspecialchars($parentHref) ?>" class="nav-link <?= $activeClass ?>" <?= $isSelfActive ? 'aria-current="page"' : '' ?>>
              <i class="nav-icon <?= htmlspecialchars($parentIcon) ?>"></i>
              <p><?= htmlspecialchars($it['label'] ?? 'Menu') ?><?php if ($hasChildren): ?><i class="nav-arrow bi bi-chevron-right"></i><?php endif; ?></p>
            </a>
            <?php if ($hasChildren): ?>
              <ul class="nav nav-treeview">
                <?php foreach ($it['children'] as $c):
                    $cIsActive = $matches($c['match'] ?? [], $uriPath);
                    $cActive   = $cIsActive ? 'active' : '';
                    $cIcon     = $c['icon'] ?? 'fa-regular fa-circle';
                    $cUrl      = (string)($c['url'] ?? '#');
                ?>
                  <li class="nav-item">
                    <a href="<?= htmlspecialchars($cUrl) ?>" class="nav-link <?= $cActive ?>" <?= $cIsActive ? 'aria-current="page"' : '' ?>>
                      <i class="nav-icon <?= htmlspecialchars($cIcon) ?>"></i>
                      <p><?= htmlspecialchars($c['label'] ?? '') ?></p>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
        <!-- (Optional) Static sections can remain below if you still needed -->
      </ul>
      <!--end::Sidebar Menu-->
    </nav>
  </div>
  <!--end::Sidebar Wrapper-->
</aside>
<!--end::Sidebar-->
