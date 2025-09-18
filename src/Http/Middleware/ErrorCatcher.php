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
                // Próbáljuk a 500-as nézetet betölteni
                $html = View::render('errors/500', [
                    'title'   => 'Hiba történt',
                    'message' => $e->getMessage(),
                ]);
            } catch (\Throwable) {
                // Ha nincs nézet, akkor minimál HTML fallback
                $msg = htmlspecialchars($e->getMessage());
                $html = "<!doctype html>
<html lang=\"hu\">
<head><meta charset=\"utf-8\"><title>500 Hiba</title></head>
<body style=\"font-family:system-ui;max-width:720px;margin:40px auto;\">
  <h1>Váratlan hiba (500)</h1>
  <p>{$msg}</p>
</body>
</html>";
            }

            return (new Response())->status(500)->html($html);
        }
    }
}
