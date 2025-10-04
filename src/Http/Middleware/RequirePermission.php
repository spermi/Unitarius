<?php
declare(strict_types=1);

namespace Http\Middleware;

use Core\Request;
use Core\Response;

/**
 * RequirePermission middleware
 *
 * Purpose:
 * - Ensures the current user is authenticated and has a specific permission.
 * - If not authenticated: redirect to login, saving the intended URL.
 * - If authenticated but missing permission: return 403.
 *
 * Usage (route wiring example):
 *   $router->get('/users', [new RequirePermission('users.view'), [UserController::class, 'index']]);
 *
 * Notes:
 * - Relies on global helpers: is_logged_in(), base_url(), can()
 * - Keep helpers in the global namespace calls with leading backslash.
 */
final class RequirePermission implements \Core\Middleware
{
    /** @var string */
    private string $permission;

    /**
     * @param string $permission Required permission name (e.g. "users.view")
     */
    public function __construct(string $permission)
    {
        $this->permission = $permission;
    }

    /**
     * Middleware pipeline handler.
     *
     * @param Request  $req
     * @param callable $next
     * @return Response
     */
    public function handle(Request $req, callable $next): Response
    {
        // Not logged in → remember where we wanted to go, then redirect to login
        if (!\is_logged_in()) {
            $_SESSION['intended'] = $req->uri();
            return (new Response())->redirect(\base_url('/login?error=unauthorized'));
        }

        // Logged in but no permission → 403 Forbidden
        if (!\can($this->permission)) {
            // Optional flash for UX; the view may render this on next page
            $_SESSION['flash_error'] = 'Nincs jogosultságod ehhez az oldalhoz.';

            // Return a minimal 403 HTML response
            // (Response does not expose a status() setter publicly in this project,
            // so we set the code here and return a simple HTML body.)
            http_response_code(403);
            return (new Response())->html(
                '<!doctype html><html lang="hu"><meta charset="utf-8">' .
                '<title>403 – Forbidden</title>' .
                '<body style="font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;padding:2rem;">' .
                '<h1 style="margin:0 0 0.5rem;">403 – Hozzáférés megtagadva</h1>' .
                '<p>Nincs megfelelő jogosultságod ehhez az oldalhoz.</p>' .
                '<p><a href="' . \base_url('/') . '">Vissza a főoldalra</a></p>' .
                '</body></html>'
            );
        }

        // All good → pass to next handler
        return $next($req);
    }
}
