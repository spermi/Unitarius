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

    // ---------------------------------------------------------
    // Register an additional view root (e.g. app/Apps/People/Views).
    // ---------------------------------------------------------
    public static function addPath(string $path): void
    {
        $path = rtrim($path, "/\\");
        if ($path !== '' && is_dir($path) && !in_array($path, self::$paths, true)) {
            self::$paths[] = $path;
        }
    }

    // ---------------------------------------------------------
    // Resolve a view file name (e.g. "people/list") against all registered roots.
    // Also supports automatic app detection (e.g. Users, People, Rbac).
    // ---------------------------------------------------------
    private static function resolve(string $name): ?string
    {
        // Logging for debugging
        //error_log('[VIEW DEBUG] requested=' . $name);

        $name = ltrim($name, "/\\") . '.php';
        $base = self::basePath();

        // --- Find the first controller class in stack trace ---
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $appName = '';
        foreach ($trace as $frame) {
            if (!empty($frame['class']) && preg_match('/App\\\\Apps\\\\([^\\\\]+)\\\\Controllers/', $frame['class'], $m)) {
                $appName = $m[1];
                break;
            }
        }

        // --- Build candidate list ---
        $candidates = [];

        // 1️ app-specific folder
        if ($appName !== '') {
            $candidates[] = "{$base}/app/Apps/{$appName}/Views/{$name}";
        }

        // 2️ global fallback
        $candidates[] = "{$base}/app/Views/{$name}";

        // Logging for debugging    
        //error_log("[VIEW DEBUG] detected app={$appName}, trying: " . implode(' | ', $candidates));

        foreach ($candidates as $file) {
            if (is_file($file)) {
                return $file;
            }
        }

        return null;
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
    