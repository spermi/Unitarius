<?php
declare(strict_types=1);

namespace Http\Middleware;

use Core\Request;
use Core\Response;

final class AuthRequired implements \Core\Middleware
{
    public function handle(Request $req, callable $next): Response
    {
        $path = $req->uri();

        // TEMP debug headers (remove later)
        header('X-Debug-Path: '.$path);
        header('X-Debug-Logged: '.(is_logged_in() ? '1' : '0'));

        // Whitelist: allow any URL that ends with /login (handles base path too)
        if (preg_match('#/login/?$#', $path)) {
            return $next($req);
        }

        if (!is_logged_in()) {
            $_SESSION['intended'] = $path;

            // Optional debug: add ?__debug_auth=1 to see plain output instead of redirect
            if (isset($_GET['__debug_auth'])) {
                header('Content-Type: text/plain; charset=utf-8');
                http_response_code(401);
                echo "DEBUG AuthRequired\npath={$path}\nlogged=0\n(no redirect because __debug_auth=1)";
                exit;
            }

            return (new Response())->redirect(base_url('/login'));
        }

        return $next($req);
    }
}
