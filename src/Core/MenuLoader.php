<?php
declare(strict_types=1);

namespace Core;

final class MenuLoader
{
    /** Load all per-app manifests from app/Apps/*/manifest.php */
    public static function load(): array
    {
        $root  = base_path('app/Apps');
        $files = glob($root.'/*/manifest.php') ?: [];
        $items = [];

        foreach ($files as $mf) {
            $cfg = require $mf;
            if (!is_array($cfg) || empty($cfg['name']) || empty($cfg['label']) || empty($cfg['prefix'])) {
                continue; // skip invalid
            }
            // defaults
            $cfg['icon']     = $cfg['icon']     ?? 'fa-regular fa-folder';
            $cfg['order']    = $cfg['order']    ?? 999;
            $cfg['match']    = $cfg['match']    ?? ['#^'.preg_quote($cfg['prefix'], '#').'#'];
            $cfg['children'] = array_map(function ($c) {
                $c['icon'] = $c['icon'] ?? 'fa-regular fa-circle'; // default child icon
                return $c;
            }, $cfg['children'] ?? []);

            $items[] = $cfg;
        }

        usort($items, fn($a,$b)=> ($a['order'] <=> $b['order']) ?: strcasecmp($a['label'],$b['label']));
        return $items;
    }
}
