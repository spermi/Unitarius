<?php
declare(strict_types=1);

namespace Core;

final class MenuLoader
{
    // Load menu items from:
    // 1) Core config: config/menu.core.php  (flat list of links)
    // 2) App manifests under: app/Apps/[any]/manifest.php (parent with optional children)
    public static function load(): array
    {
        $items = [];

        $baseDir  = dirname(__DIR__, 2);
        $appsRoot = function_exists('base_path') ? base_path('app/Apps') : $baseDir . '/app/Apps';
        $cfgCore  = function_exists('base_path') ? base_path('config/menu.core.php') : $baseDir . '/config/menu.core.php';

        // 1) Core menu
        if (is_file($cfgCore)) {
            /** @var array<int,array> $core */
            $core = require $cfgCore;

            foreach ($core as $idx => $ci) {
                if (!is_array($ci) || empty($ci['label']) || empty($ci['url'])) {
                    continue;
                }
                $path = (string)(parse_url((string)$ci['url'], PHP_URL_PATH) ?? '/');

                $items[] = [
                    'name'     => 'core-' . self::slug((string)$ci['label']) . '-' . $idx,
                    'label'    => (string)$ci['label'],
                    'icon'     => (string)($ci['icon']  ?? 'fa-solid fa-gauge'),
                    'order'    => (int)($ci['order'] ?? -100 + $idx),
                    'match'    => is_array($ci['match'] ?? null) ? $ci['match'] : ['#^' . preg_quote($path, '#') . '/?$#'],
                    'prefix'   => $path,
                    'url'      => (string)$ci['url'],
                    'children' => [],
                ];
            }
        }

        // 2) App manifests
        $files = glob($appsRoot . '/*/manifest.php') ?: [];

        foreach ($files as $mf) {
            /** @var array $cfg */
            $cfg = require $mf;

            if (!is_array($cfg) || empty($cfg['name']) || empty($cfg['label']) || !isset($cfg['prefix'])) {
                continue;
            }

            $cfg['icon']  = $cfg['icon']  ?? 'fa-regular fa-folder';
            $cfg['order'] = $cfg['order'] ?? 999;

            $prefix = (string)$cfg['prefix'];
            $cfg['match'] = $cfg['match'] ?? ['#^' . preg_quote($prefix, '#') . '#'];

            $children = array_map(static function (array $c): array {
                $c['icon']  = $c['icon']  ?? 'fa-regular fa-angles-right';
                $c['match'] = is_array($c['match'] ?? null) ? $c['match'] : [];
                $c['order'] = $c['order'] ?? 999;
                return $c;
            }, $cfg['children'] ?? []);

            usort($children, static fn($a, $b) =>
                ($a['order'] <=> $b['order']) ?: strcasecmp($a['label'] ?? '', $b['label'] ?? '')
            );

            $cfg['children'] = $children;

            $items[] = $cfg;
        }

        usort($items, static function ($a, $b) {
            $ord = ((int)($a['order'] ?? 999)) <=> ((int)($b['order'] ?? 999));
            return $ord !== 0 ? $ord : strcasecmp((string)($a['label'] ?? ''), (string)($b['label'] ?? ''));
        });

        return $items;
    }

    private static function slug(string $text): string
    {
        $s = strtolower(trim($text));
        $s = preg_replace('/[^a-z0-9]+/i', '-', $s) ?? '';
        return trim($s, '-');
    }
}
