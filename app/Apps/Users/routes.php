<?php
declare(strict_types=1);

use Core\Router;
use Http\Middleware\RequirePermission;
use App\Apps\Users\Controllers\UserController;

/**
 * Users app routes
 *
 * Mounted automatically by the framework's app loader.
 *
 * Endpoints:
 *  - GET /users                 → Users listing (requires "users.view")
 *
 * Notes:
 *  - Uses route-level middleware: RequirePermission($perm)
 *  - Controller classes are namespaced under App\Apps\Users\Controllers
 */
return static function (Router $router): void {

    // ---------------------------------------------------------
    // --- USERS LIST (protected) 
    // ---------------------------------------------------------
    $router->get('/users', [
        new RequirePermission('users.view'),
        [UserController::class, 'index']
    ]);

};
