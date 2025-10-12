<?php
declare(strict_types=1);

use Core\Router;
use Http\Middleware\RequirePermission;
use App\Apps\Users\Controllers\UserController;

// ---------------------------------------------------------
// Users app routes
// Mounted automatically by the framework's app loader.
// Endpoints:
//  - GET  /users              → Users listing (requires "users.view")
//  - GET  /users/{id}/edit    → Edit form (requires "users.manage")
//  - POST /users/{id}/edit    → Update user (requires "users.manage")
// Notes:
//  - Uses route-level middleware: RequirePermission($perm)
//  - Controller classes are namespaced under App\Apps\Users\Controllers
// ---------------------------------------------------------
return static function (Router $router): void {

    // ---------------------------------------------------------
    // --- USERS LIST (protected)
    // ---------------------------------------------------------
    $router->get('/users', [
        new RequirePermission('users.view'),
        [UserController::class, 'index']
    ]);

    // ---------------------------------------------------------
    // --- USERS EDIT (protected)
    // ---------------------------------------------------------
    $router->get('/users/{id}/edit', [
        new RequirePermission('users.manage'),
        [UserController::class, 'editForm']
    ]);

    $router->post('/users/{id}/edit', [
        new RequirePermission('users.manage'),
        [UserController::class, 'edit']
    ]);
};
