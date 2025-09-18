<?php
declare(strict_types=1);

namespace Core;

final class View
{
    private static function basePath(): string
    {
        // from src/Core → project root
        return dirname(__DIR__, 2);
    }

    public static function render(string $view, array $data = [], ?string $layout = 'layout'): string
    {
        $root = self::basePath();
        $viewFile = $root . '/app/Views/' . trim($view, '/\\') . '.php';

        if (!is_file($viewFile)) {
            http_response_code(500);
            return "View not found: {$view} (looked for: {$viewFile})";
        }

        extract($data, EXTR_SKIP);
        $e = static fn(?string $s): string => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

        ob_start();
        require $viewFile;
        $content = (string)ob_get_clean();

        if ($layout === null) {
            return $content;
        }

        $layoutFile = $root . '/app/Views/' . trim($layout, '/\\') . '.php';
        if (!is_file($layoutFile)) {
            return $content;
        }

        ob_start();
        require $layoutFile;
        return (string)ob_get_clean();
    }
}
