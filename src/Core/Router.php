<?php
declare(strict_types=1);

namespace Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->map('GET', $this->normalize($path), $handler);
    }

    private function map(string $method, string $path, callable|array $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->requestPath($uri);

        $handler = $this->routes[$method][$path] ?? null;
        if ($handler === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        if (is_array($handler)) {
            [$class, $action] = $handler;
            $handler = [new $class(), $action];
        }

        echo (string) call_user_func($handler);
    }

    private function requestPath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Derive app base from SCRIPT_NAME, trimming trailing /public or /public/index.php
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $appBase = preg_replace('#/public(?:/index\.php)?$#', '', $script) ?? '';
        $appBase = rtrim($appBase, '/');

        if ($appBase !== '' && str_starts_with($path, $appBase)) {
            $path = substr($path, strlen($appBase));
        } else {
            // Fallback: use dirname(SCRIPT_NAME)
            $autoBase = rtrim(dirname($script), '/\\');
            if ($autoBase !== '' && str_starts_with($path, $autoBase)) {
                $path = substr($path, strlen($autoBase));
            }
        }

        return $this->normalize($path);
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }
}
