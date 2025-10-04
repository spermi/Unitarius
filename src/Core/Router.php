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

    public function post(string $path, callable|array $handler): void
    {
        $this->map('POST', $this->normalize($path), $handler);
    }

    private function map(string $method, string $path, callable|array $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }

    /**
     * Return the handler output as string, or null if no route matched.
     * DO NOT send output here (no echo) – let front controller send it.
     */
    public function dispatch(string $method, string $uri): ?string
    {
        $method  = strtoupper($method);
        $path    = $this->requestPath($uri);

        $handler = $this->routes[$method][$path] ?? null;
        if ($handler === null) {
            // No headers / no echo here – let index.php decide (404 vs. custom page)
            return null;
        }

        // Cases we support:
        // 1) callable (closure or [obj,'method'])
        // 2) [ClassName::class, 'method']               → instantiate class, call method
        // 3) [MiddlewareInstance, [ClassName,'method']] → IGNORE middleware here, call controller
        //    (Per-route middleware pipeline will be handled at Kernel level in a later step.)
        if (is_array($handler)) {

            // (3) route-level middleware array form → use only the controller spec for now
            if (
                count($handler) === 2
                && is_object($handler[0])                        // e.g. new RequirePermission('users.view')
                && is_array($handler[1])                         // controller spec
                && isset($handler[1][0], $handler[1][1])
            ) {
                $controllerSpec = $handler[1];
                if (is_string($controllerSpec[0])) {
                    $handler = [new $controllerSpec[0](), $controllerSpec[1]];
                } else {
                    $handler = $controllerSpec; // already callable like [$obj,'method']
                }
            }
            // (2) classic [ClassName::class, 'method']
            elseif (isset($handler[0], $handler[1]) && is_string($handler[0]) && is_string($handler[1])) {
                $handler = [new $handler[0](), $handler[1]];
            }
            // else: assume it's already a valid callable (closure or [$obj,'method'])
        }

        // Return the result, caller decides how to send
        $result = \call_user_func($handler);

        // normalize to string
        return is_string($result) ? $result : (string)$result;
    }

    private function requestPath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Derive app base from SCRIPT_NAME, trimming trailing /public or /public/index.php
        $script  = $_SERVER['SCRIPT_NAME'] ?? '';
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
