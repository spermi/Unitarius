<?php
namespace Core;

final class Kernel
{
    /** @var array<int, Middleware> */
    private array $stack = [];
    public function push(Middleware $m): void { $this->stack[] = $m; }

    public function handle(Request $req, callable $controller): Response
    {
        $runner = array_reduce(
            array_reverse($this->stack),
            fn($next, Middleware $m) => fn(Request $r) => $m->handle($r, $next),
            fn(Request $r) => $controller($r)
        );
        return $runner($req);
    }
}
