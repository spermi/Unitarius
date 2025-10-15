<?php
declare(strict_types=1);

use Core\Router;
use Http\Middleware\RequirePermission;
use App\Apps\Adatlap\Controllers\AdatlapController;
/**
 * Adatlap app routes
 *
 * Mounted automatically by the framework's app loader.
 *
 * Endpoints:
 *  - GET /adatlap/studies        → Studies view page (requires "adatlap.lelkesz")
 */
return static function (Router $router): void {

    // ---------------------------------------------------------
    // --- TANULMÁNYOK (Lelkészhez kötött)
    // ---------------------------------------------------------
    $router->get('/adatlap/studies', [
        new RequirePermission('adatlap.lelkesz'),
        [AdatlapController::class, 'studies']
    ]);


};
