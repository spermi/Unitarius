<?php
declare(strict_types=1);

// --------------------------------------------------------------
// Normalize current URI against base path (APP_BASE_PATH / APP_URL)
// --------------------------------------------------------------
$rawUri  = $_SERVER['REQUEST_URI'] ?? '/';
$uriPath = parse_url($rawUri, PHP_URL_PATH) ?? '/';

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
// Load + RBAC-filter menu (same logic as sidebar)
// --------------------------------------------------------------
$menu = \Core\MenuLoader::load();

$filterMenuByRBAC = function(array $menu): array {
    $out = [];
    foreach ($menu as $item) {
        $parentAllowed = empty($item['perm']) || \can((string)$item['perm']);
        $children = $item['children'] ?? [];
        $children = array_values(array_filter($children, function($c){
            return empty($c['perm']) || \can((string)$c['perm']);
        }));
        if ($parentAllowed || !empty($children)) {
            $item['children'] = $children;
            $out[] = $item;
        }
    }
    return $out;
};

$menu = $filterMenuByRBAC($menu);

// --------------------------------------------------------------
// Find active parent & child using 'match' regex arrays
// --------------------------------------------------------------
$safeMatch = function (?array $rxs, string $u): bool {
    if (empty($rxs)) return false;
    foreach ($rxs as $rx) {
        if (@preg_match($rx, '') === false) continue;
        if (@preg_match($rx, $u) === 1) return true;
    }
    return false;
};

$activeParent = null;
$activeChild  = null;

foreach ($menu as $parent) {
    $parentIsActive = $safeMatch($parent['match'] ?? [], $uriPath);
    $foundChild = null;

    foreach ($parent['children'] ?? [] as $child) {
        if ($safeMatch($child['match'] ?? [], $uriPath)) {
            $foundChild = $child;
            break;
        }
    }

    if ($foundChild || $parentIsActive) {
        $activeParent = $parent;
        $activeChild  = $foundChild;
        break;
    }
}

// --------------------------------------------------------------
// Render breadcrumbs
// --------------------------------------------------------------
?>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-2">
    <li class="breadcrumb-item">
      <a href="<?= htmlspecialchars(base_url('/')) ?>">Kezdőlap</a>
    </li>

    <?php if ($activeParent): ?>
      <?php if ($activeChild): ?>
        <li class="breadcrumb-item">
          <span><?= htmlspecialchars((string)($activeParent['label'] ?? '')) ?></span>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
          <?= htmlspecialchars((string)($activeChild['label'] ?? '')) ?>
        </li>
      <?php else: ?>
        <li class="breadcrumb-item active" aria-current="page">
          <?= htmlspecialchars((string)($activeParent['label'] ?? '')) ?>
        </li>
      <?php endif; ?>
    <?php endif; ?>
  </ol>
</nav>
