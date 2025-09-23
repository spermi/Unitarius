<?php
declare(strict_types=1);

namespace Http\Middleware;

use Core\Request;
use Core\Response;

final class GuestOnly implements \Core\Middleware
{
    public function handle(Request $req, callable $next): Response
    {
        if (is_logged_in()) {
            return (new Response())->redirect(base_url('/'));
        }
        return $next($req);
    }
}
