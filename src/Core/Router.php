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
     * For dynamic routes with tokens like `/foo/{id}/edit`, the `{id}` part is captured
     * as a named group and passed to the controller as a single associative array param.
     *
     * @return array{0: callable|null, 1: array<int,\Core\Middleware>} handler + route middlewares
     */
    public function resolve(string $method, string $uri): array
    {
        $method = strtoupper($method);
        $path   = $this->requestPath($uri);

        // 1) Exact match first (fast path)
        $spec = $this->routes[$method][$path] ?? null;
        if ($spec !== null) {
            [$handler, $routeMw] = $this->hydrateHandler($spec);
            return [$handler, $routeMw];
        }

        // 2) Dynamic match: iterate registered routes with {param} tokens
        $registered = $this->routes[$method] ?? [];
        foreach ($registered as $routePath => $routeSpec) {
            if (strpos($routePath, '{') === false) {
                continue; // skip non-dynamic entries
            }

            // Compile pattern: /foo/{id}/bar → #^/foo/(?P<id>[^/]+)/bar$#
            $pattern = $this->compilePattern($routePath);
            if ($pattern === null) {
                continue;
            }

            if (preg_match($pattern, $path, $m) === 1) {
                // Extract named params only
                $params = [];
                foreach ($m as $k => $v) {
                    if (!is_int($k)) {
                        $params[$k] = $v;
                    }
                }

                // Build handler + middlewares from spec
                [$callable, $routeMw] = $this->hydrateHandler($routeSpec);

                if ($callable === null) {
                    return [null, []];
                }

                // Wrap callable to pass $params as single argument (array $params)
                $handler = function () use ($callable, $params) {
                    return \call_user_func($callable, $params);
                };

                return [$handler, $routeMw];
            }
        }

        // No match
        return [null, []];
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

    /**
     * Turn a route with tokens into a regex pattern with named groups.
     * e.g. "/rbac/roles/{id}/edit" → "#^/rbac/roles/(?P<id>[^/]+)/edit$#"
     */
    private function compilePattern(string $routePath): ?string
    {
        // Basic sanity: must start with '/'
        if ($routePath === '' || $routePath[0] !== '/') {
            return null;
        }
        $regex = preg_replace(
            '#\{([a-zA-Z0-9_]+)\}#',
            '(?P<$1>[^/]+)',
            $routePath
        );
        if (!is_string($regex)) {
            return null;
        }
        return '#^' . $regex . '$#';
    }

    /**
     * Build a callable handler and collect per-route middleware from a route spec.
     * Keeps your original 3-spec behavior.
     *
     * @param callable|array $spec
     * @return array{0: callable|null, 1: array<int,\Core\Middleware>}
     */
    private function hydrateHandler(callable|array $spec): array
    {
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

        if (!is_callable($handler)) {
            return [null, $routeMw];
        }
        return [$handler, $routeMw];
    }
}
