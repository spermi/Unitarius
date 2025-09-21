<?php
declare(strict_types=1);

namespace Core;

final class MenuLoader
{
    // Load all per-app manifests (glob: app/Apps/*/manifest.php)
    public static function load(): array
    {
        // Use helper if exists; fallback to relative path
        $appsRoot = function_exists('base_path')
            ? base_path('app/Apps')
            : dirname(__DIR__, 2) . '/app/Apps';

        $files = glob($appsRoot . '/*/manifest.php') ?: [];
        $items = [];

        foreach ($files as $mf) {
            /** @var array $cfg */
            $cfg = require $mf;

            // Basic validation
            if (!is_array($cfg) || empty($cfg['name']) || empty($cfg['label']) || empty($cfg['prefix'])) {
                continue; // skip invalid manifests
            }

            // Defaults for parent
            $cfg['icon']  = $cfg['icon']  ?? 'fa-regular fa-folder'; // default folder icon
            $cfg['order'] = $cfg['order'] ?? 999;
            $cfg['match'] = $cfg['match'] ?? ['#^' . preg_quote($cfg['prefix'], '#') . '#'];

            // Normalize children + defaults
            $children = array_map(static function (array $c): array {
                $c['icon']  = $c['icon']  ?? 'fa-regular fa-angles-right'; // default subfolder icon
                $c['match'] = $c['match'] ?? [];
                $c['order'] = $c['order'] ?? 999;
                return $c;
            }, $cfg['children'] ?? []);

            // Sort children by order, then label
            usort($children, static fn($a, $b) =>
                ($a['order'] <=> $b['order']) ?: strcasecmp($a['label'] ?? '', $b['label'] ?? '')
            );

            $cfg['children'] = $children;

            $items[] = $cfg;
        }

        // Sort parents by order, then label
        usort($items, static function ($a, $b) {
            $ord = ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
            return $ord !== 0 ? $ord : strcasecmp($a['label'] ?? '', $b['label'] ?? '');
        });

        return $items;
    }
}
