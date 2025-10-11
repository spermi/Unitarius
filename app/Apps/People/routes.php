<?php
declare(strict_types=1);

use Core\Router;
use Http\Middleware\RequirePermission;
use App\Apps\People\Controllers\PeopleController;

/**
 * People app routes
 *
 * Mounted automatically by the framework's app loader.
 *
 * Endpoints:
 *  - GET /people        → People list (requires "people.view")
 *  - GET /people/{id}   → (future) single person view
 */
return static function (Router $router): void {

    // ---------------------------------------------------------
    // --- PEOPLE LIST (protected)
    // ---------------------------------------------------------
    $router->get('/people', [
        new RequirePermission('people.view'),
        [PeopleController::class, 'index']
    ]);

    // Optional alias with trailing slash
    $router->get('/people/', [
        new RequirePermission('people.view'),
        [PeopleController::class, 'index']
    ]);

};
