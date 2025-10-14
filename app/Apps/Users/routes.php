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
//  - GET  /users/new          → Create form (requires "users.create")
//  - POST /users/new          → Save new user (requires "users.create")
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
    // --- USERS CREATE (protected)
    // ---------------------------------------------------------
    $router->get('/users/new', [
        new RequirePermission('users.create'),
        [UserController::class, 'createForm']
    ]);

    $router->post('/users/new', [
        new RequirePermission('users.create'),
        [UserController::class, 'createSave']
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

    // ---------------------------------------------------------
    // --- USERS DELETE (protected, permanent delete)
    // ---------------------------------------------------------
    $router->post('/users/{id}/delete', [
        new RequirePermission('users.delete'),
        [UserController::class, 'delete']
    ]);

    // ---------------------------------------------------------
    // --- USERS VIEW (protected)
    // ---------------------------------------------------------
    $router->get('/users/{id}/view', [
        new RequirePermission('users.view'),
        [UserController::class, 'view']
    ]);

    // ---------------------------------------------------------
    // --- USERS ADMIN PANEL (only for users.admin permission) ---
    // ---------------------------------------------------------
    $router->get('/users/deleted', [
        new RequirePermission('users.admin'),
        [UserController::class, 'deleted_List']
    ]);

    // ---------------------------------------------------------
    // --- RESTORE deleted user (POST) ---
    // ---------------------------------------------------------
    $router->post('/users/{id}/restore', [
        new RequirePermission('users.admin'),
        [UserController::class, 'restore']
    ]);

};
