<?php
namespace Http\Middleware;

use Core\{Middleware, Request, Response, View};

final class ErrorCatcher implements Middleware
{
    public function handle(Request $req, callable $next): Response
    {
        try {
            return $next($req);
        } catch (\Throwable $e) {
            try {
                // Try rendering the AdminLTE 500 view
                $html = View::render('errors/500', [
                    'title'   => 'Server Error',
                    'message' => $e->getMessage(),
                ], null);
            } catch (\Throwable) {
                // Minimal HTML fallback if the view fails
                $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                $html = "<!doctype html>
<html lang=\"en\">
<head><meta charset=\"utf-8\"><title>500 Error</title></head>
<body style=\"font-family:system-ui;max-width:720px;margin:40px auto;\">
  <h1>Unexpected error (500)</h1>
  <p>{$msg}</p>
</body>
</html>";
            }

            return (new Response())->status(500)->html($html);
        }
    }
}
