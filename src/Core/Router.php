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
     * Resolve a route into (callable handler, route middlewares).
     *
     * Supported specs:
     *  1) callable (closure or [$object,'method'])
     *  2) [ClassName::class, 'method']                       → instantiate class, call method
     *  3) [MiddlewareInstance, [ClassName::class,'method']]  → collect middleware, then controller
     *
     * @return array{0: callable|null, 1: array<int,\Core\Middleware>} handler + route middlewares
     */
    public function resolve(string $method, string $uri): array
    {
        $method = strtoupper($method);
        $path   = $this->requestPath($uri);

        $spec = $this->routes[$method][$path] ?? null;
        if ($spec === null) {
            return [null, []];
        }

        $routeMw = [];
        $handler = $spec;

        if (is_array($spec)) {
            // (3) [MiddlewareInstance, [ClassName,'method']]
            if (
                count($spec) === 2
                && is_object($spec[0])
                && $spec[0] instanceof \Core\Middleware
                && is_array($spec[1])
                && isset($spec[1][0], $spec[1][1])
            ) {
                $routeMw[] = $spec[0];
                $controllerSpec = $spec[1];

                if (is_string($controllerSpec[0])) {
                    $handler = [new $controllerSpec[0](), $controllerSpec[1]];
                } else {
                    $handler = $controllerSpec; // already callable like [$obj,'method']
                }
            }
            // (2) [ClassName::class, 'method']
            elseif (isset($spec[0], $spec[1]) && is_string($spec[0]) && is_string($spec[1])) {
                $handler = [new $spec[0](), $spec[1]];
            }
            // else: assume it's already a valid callable (closure or [$obj,'method'])
        }

        return [$handler, $routeMw];
    }

    /**
     * Backward-compatible dispatch.
     * Uses resolve() but ignores per-route middleware (kept for BC).
     * Prefer using resolve() + Kernel::handleWith() in the front controller.
     */
    public function dispatch(string $method, string $uri): ?string
    {
        [$handler, /* $routeMw */] = $this->resolve($method, $uri);
        if ($handler === null) {
            // No headers / no echo here – let index.php decide (404 vs. custom page)
            return null;
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
