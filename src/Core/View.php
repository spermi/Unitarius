<?php
declare(strict_types=1);

namespace Core;

final class View
{
    /** @var string[] */
    private static array $paths = [];

    private static function basePath(): string
    {
        // from src/Core → project root
        return dirname(__DIR__, 2);
    }

    /**
     * Register an additional view root (e.g. app/Apps/People/Views).
     */
    public static function addPath(string $path): void
    {
        $path = rtrim($path, "/\\");
        if ($path !== '' && is_dir($path) && !in_array($path, self::$paths, true)) {
            self::$paths[] = $path;
        }
    }

    /**
     * Resolve a view file name (e.g. "people/list") against all registered roots.
     */
    private static function resolve(string $name): ?string
    {
        $name = ltrim($name, "/\\") . '.php';

        // 1) Extra roots (e.g. app/Apps/*/Views)
        foreach (self::$paths as $root) {
            $file = $root . DIRECTORY_SEPARATOR . $name;
            if (is_file($file)) {
                return $file;
            }
        }

        // 2) Legacy/default root: app/Views
        $fallback = self::basePath() . '/app/Views/' . $name;
        return is_file($fallback) ? $fallback : null;
    }

    public static function render(string $view, array $data = [], ?string $layout = 'layout'): string
    {
        $viewFile = self::resolve($view);
        if ($viewFile === null) {
            http_response_code(500);
            return "View not found: {$view}";
        }

        extract($data, EXTR_SKIP);
        $e = static fn(?string $s): string => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

        ob_start();
        /** @noinspection PhpIncludeInspection */
        require $viewFile;
        $content = (string)ob_get_clean();

        if ($layout === null) {
            return $content;
        }

        $layoutFile = self::resolve($layout) ?? (self::basePath() . '/app/Views/' . trim($layout, '/\\') . '.php');
        if (!is_file($layoutFile)) {
            return $content;
        }

        ob_start();
        /** @noinspection PhpIncludeInspection */
        require $layoutFile;
        return (string)ob_get_clean();
    }
}
