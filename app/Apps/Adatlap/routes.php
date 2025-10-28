<?php
declare(strict_types=1);

use Core\Router;
use Http\Middleware\RequirePermission;
use App\Apps\Adatlap\Controllers\AdatlapController;
use App\Apps\Adatlap\Controllers\FamilyController;

/**
 * Adatlap app routes
 *
 * Mounted automatically by the framework's app loader.
 *
 * Endpoints:
 *  - GET /adatlap/studies              → Studies view page (requires "adatlap.lelkesz")
 *  - GET /adatlap/family               → Family list (requires "adatlap.lelkesz")
 *  - GET /adatlap/family/{uuid}        → Family details (requires "adatlap.lelkesz")
 *  - POST /adatlap/family/member/save  → Save or update family member
 *  - GET /adatlap/family/tree/{uuid}   → Return family tree JSON
 *  - GET /adatlap/family/create        → Show the form for creating a new family (requires "adatlap.family.create")
 *  - POST /adatlap/family/store        → Store the new family in the database (requires "adatlap.family.create")
 */

return static function (Router $router): void {



    $router->get('/adatlap', [
        new RequirePermission('adatlap.lelkesz'),
        [AdatlapController::class, 'index'] // új redirect handler
    ]);

    // ---------------------------------------------------------
    // --- TANULMÁNYOK (Lelkészhez kötött)
    // ---------------------------------------------------------
    $router->get('/adatlap/studies', [
        new RequirePermission('adatlap.lelkesz'),
        [AdatlapController::class, 'studies']
    ]);

    $router->post('/adatlap/studies/save', [
        new RequirePermission('adatlap.lelkesz'),
        [AdatlapController::class, 'saveStudies']
    ]);

    // Pastor profil + education lista (csak saját UUID szerkeszthető)
    $router->get('/adatlap/pastor/{uuid}', [
        new RequirePermission('adatlap.lelkesz'),
        [AdatlapController::class, 'pastorProfile']
    ]);

    // Új education bejegyzés felvétele az adott lelkészhez
    $router->post('/adatlap/pastor/{uuid}/education/store', [
        new RequirePermission('adatlap.lelkesz'),
        [AdatlapController::class, 'storeEducation']
    ]);

    // ---------------------------------------------------------
    // --- CSALÁDOK (FamilyController)
    // ---------------------------------------------------------
    $router->get('/adatlap/family', [
        new RequirePermission('adatlap.lelkesz'),
        [FamilyController::class, 'index']  
    ]);

    $router->get('/adatlap/family/{uuid}', [
        new RequirePermission('adatlap.lelkesz'),
        [FamilyController::class, 'show']   
    ]);

    $router->post('/adatlap/family/member/save', [
        new RequirePermission('adatlap.lelkesz'),
        [FamilyController::class, 'saveMember']
    ]);

    $router->get('/adatlap/family/tree/{uuid}', [
        new RequirePermission('adatlap.lelkesz'),
        [FamilyController::class, 'tree']
    ]);

    // ---------------------------------------------------------
    // --- ÚJ CSALÁD HOZZÁADÁSA (FamilyController)
    // ---------------------------------------------------------
    $router->get('/adatlap/family/create', [
        new RequirePermission('adatlap.family.create'),
        [FamilyController::class, 'create']  // Család form megjelenítése
    ]);

    $router->post('/adatlap/family/store', [
        new RequirePermission('adatlap.family.create'),
        [FamilyController::class, 'store']  //Család adatainak mentése
    ]);

};
