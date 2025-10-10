<?php
declare(strict_types=1);

namespace Http\Middleware;

use Core\Request;
use Core\Response;
use Core\View;

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

            $html = View::render('errors/403', [
                'title'   => 'Access Forbidden',
                'message' => $_SESSION['flash_error'] ?? 'You do not have permission to access this resource.',
            ], null);

            return (new Response())
                ->status(403)
                ->html($html);
        }

        // All good → pass to next handler
        return $next($req);
    }
}
