<?php
declare(strict_types=1);

use Core\Router;
use Http\Middleware\RequirePermission;
use App\Apps\Users\Controllers\UserController;
use App\Apps\Users\Controllers\RbacController;

/**
 * Users app routes
 *
 * Mounted automatically by the framework's app loader.
 *
 * Endpoints:
 *  - GET /users                 → Users listing (requires "users.view")
 *  - GET /rbac                  → RBAC dashboard (requires "rbac.manage")
 *  - GET /rbac/roles            → Roles list
 *  - GET /rbac/permissions      → Permissions list
 *  - GET /rbac/assignments      → User/Role/Permission relationships
 *
 * Notes:
 *  - Uses route-level middleware: RequirePermission($perm)
 *  - Controller classes are namespaced under App\Apps\Users\Controllers
 */
return static function (Router $router): void {

    // --- USERS LIST (protected) ---
    $router->get('/users', [
        new RequirePermission('users.view'),
        [UserController::class, 'index']
    ]);

    // --- RBAC DASHBOARD (protected) ---
    $router->get('/rbac', [
        new RequirePermission('rbac.manage'),
        [RbacController::class, 'index']
    ]);

    // --- RBAC ADMIN SUBPAGES ---
    $router->get('/rbac/roles', [
        new RequirePermission('rbac.manage'),
        [RbacController::class, 'roles']
    ]);

    $router->get('/rbac/permissions', [
        new RequirePermission('rbac.manage'),
        [RbacController::class, 'permissions']
    ]);

    $router->get('/rbac/assignments', [
        new RequirePermission('rbac.manage'),
        [RbacController::class, 'assignments']
    ]);
};
