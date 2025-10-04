<?php
declare(strict_types=1);

use Core\Router;
use Http\Middleware\RequirePermission;
use App\Apps\Users\Controllers\UserController;
use App\Apps\Users\Controllers\RbacController;

/**
 * Users app routes
 *
 * Mounted by the framework's app loader.
 *
 * Endpoints:
 *  - GET /users  → Users listing (requires "users.view")
 *  - GET /rbac   → RBAC dashboard (requires "rbac.manage")
 *
 * Notes:
 *  - Uses route-level middleware: RequirePermission($perm)
 *  - Controller classes are namespaced under App\Apps\Users\Controllers
 */
return static function (Router $router): void {

    // Users list (protected)
    $router->get('/users', [
        new RequirePermission('users.view'),
        [UserController::class, 'index']
    ]);

    // RBAC dashboard (protected)
    $router->get('/rbac', [
        new RequirePermission('rbac.manage'),
        [RbacController::class, 'index']
    ]);
};
