<?php
namespace Http\Middleware;
use Core\{Middleware, Request, Response};

final class TrailingSlash implements Middleware
{
    public function handle(Request $req, callable $next): Response
    {
        $u = $req->uri();
        if ($u !== '/' && str_ends_with($u, '/')) {
            return (new Response())->redirect(rtrim($u,'/'));
        }
        return $next($req);
    }
}
