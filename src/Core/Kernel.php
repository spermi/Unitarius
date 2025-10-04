<?php
namespace Core;

final class Kernel
{
    /** @var array<int, Middleware> */
    private array $stack = [];

    public function push(Middleware $m): void
    {
        $this->stack[] = $m;
    }

    /**
     * Run the middleware stack then the controller.
     * Keeps backward compatibility.
     */
    public function handle(Request $req, callable $controller): Response
    {
        $runner = array_reduce(
            array_reverse($this->stack),
            fn($next, Middleware $m) => fn(Request $r) => $m->handle($r, $next),
            fn(Request $r) => $controller($r)
        );
        return $runner($req);
    }

    /**
     * Run the global stack + an extra per-route stack, then the controller.
     * Route stack executes AFTER the global stack in the composed pipeline.
     *
     * @param Request            $req
     * @param array<int,Middleware> $routeStack
     * @param callable           $controller receives (Request): Response
     */
    public function handleWith(Request $req, array $routeStack, callable $controller): Response
    {
        // Merge stacks in order: global (this->stack) first, then route-specific
        $combined = array_merge($this->stack, $routeStack);

        $runner = array_reduce(
            array_reverse($combined),
            fn($next, Middleware $m) => fn(Request $r) => $m->handle($r, $next),
            fn(Request $r) => $controller($r)
        );
        return $runner($req);
    }
}
