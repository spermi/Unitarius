<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Core/helpers.php';

use Core\ErrorHandler;
use Core\Router;
use Core\View;
use App\Controllers\DashboardController;

// ---------------------------------------------------------
// Load .env if available
// ---------------------------------------------------------
$basePath = dirname(__DIR__);
$envFile  = $basePath . '/.env';

if (is_file($envFile)) {
    try {
        (Dotenv\Dotenv::createImmutable($basePath))->load();
    } catch (Throwable) {
        // ignore env loading error in minimal setup
    }
}

// ---------------------------------------------------------
// Determine environment (default: production)
// ---------------------------------------------------------
$appEnv = $_ENV['APP_ENV'] ?? 'production';
ErrorHandler::register($appEnv);

// ---------------------------------------------------------
// Bootstrap router
// ---------------------------------------------------------
$router = new Router();

// Handle base path (when app is in subfolder, e.g. http://localhost/unitarius)
$rawUri   = $_SERVER['REQUEST_URI'] ?? '/';
$uriPath  = parse_url($rawUri, PHP_URL_PATH) ?? '/';
$baseSub  = rtrim((string)($_ENV['APP_BASE_PATH'] ?? ''), '/'); // e.g. "/unitarius"

if ($baseSub !== '' && str_starts_with($uriPath, $baseSub)) {
    $uriPath = substr($uriPath, strlen($baseSub)) ?: '/';
}

// Normalize trailing slash (except root)
if ($uriPath !== '/' ) {
    $uriPath = rtrim($uriPath, '/');
    if ($uriPath === '') { $uriPath = '/'; }
}

// ---------------------------------------------------------
// Mount all app routes automatically: app/Apps/*/routes.php
// ---------------------------------------------------------
foreach (glob($basePath . '/app/Apps/*/routes.php') as $file) {
    $mount = require $file;
    if (is_callable($mount)) {
        $mount($router);
    }
}

// ---------------------------------------------------------
// Core routes
// ---------------------------------------------------------
$router->get('/', [DashboardController::class, 'index']);

// ---------------------------------------------------------
// Register all Apps view paths: app/Apps/*/Views
// ---------------------------------------------------------
foreach (glob($basePath . '/app/Apps/*/Views', GLOB_ONLYDIR) as $viewsDir) {
    View::addPath($viewsDir);
}

// ---------------------------------------------------------
// Dispatch request
// ---------------------------------------------------------
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $uriPath);
