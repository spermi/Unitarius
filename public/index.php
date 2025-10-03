<?php
declare(strict_types=1);

ini_set('display_errors','1');
ini_set('display_startup_errors','1');
ini_set('log_errors','1');
error_reporting(E_ALL);

// ---- Session bootstrap ----
$sessionPath = dirname(__DIR__) . '/storage/sessions';
if (is_dir($sessionPath) || @mkdir($sessionPath, 0777, true)) {
    ini_set('session.save_path', $sessionPath);
}
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

session_name('UNITARIUSSESS');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => parse_url($_SERVER['BASE_URI'] ?? '/', PHP_URL_PATH) ?: '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();
// --------------------------------------------

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Core/Helpers.php';

use Core\ErrorHandler;
use Core\Router;
use Core\View;
use Core\Request;
use Core\Response;
use Core\Kernel;
use App\Controllers\DashboardController;
use App\Controllers\AuthController;
use Http\Middleware\{ErrorCatcher, AuthRequired}; // TrailingSlash ideiglenesen OUT

// ---------------------------------------------------------
// Load .env if available
// ---------------------------------------------------------
$basePath = dirname(__DIR__);
$envFile  = $basePath . '/.env';

if (is_file($envFile)) {
    try {
        (Dotenv\Dotenv::createImmutable($basePath))->load();
    } catch (Throwable) { /* ignore */ }
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
$rawUri  = $_SERVER['REQUEST_URI'] ?? '/';
$uriPath = parse_url($rawUri, PHP_URL_PATH) ?? '/';

// Prefer APP_BASE_PATH, else derive from APP_URL path
$baseSub = rtrim((string)($_ENV['APP_BASE_PATH'] ?? ''), '/');
if ($baseSub === '') {
    $baseFromUrl = parse_url($_ENV['APP_URL'] ?? '', PHP_URL_PATH);
    if (is_string($baseFromUrl) && $baseFromUrl !== '') {
        $baseSub = rtrim($baseFromUrl, '/'); // pl. "/unitarius"
    }
}

if ($baseSub !== '' && str_starts_with($uriPath, $baseSub)) {
    $uriPath = substr($uriPath, strlen($baseSub)) ?: '/';
}

// Normalize trailing slash (except root)
if ($uriPath !== '/') {
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
$router->get('/login',  [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'doLogin']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/logout', [AuthController::class, 'logout']);
// Favicon
$router->get('/favicon.ico', fn() => file_get_contents(__DIR__ . '/favicon.ico'));

// --- Google OAuth routes ---
$router->get('/auth/google', [AuthController::class, 'googleRedirect']);
$router->get('/auth/google/callback', [AuthController::class, 'googleCallback']);

// Favicon
$router->get('/favicon.ico', fn() => file_get_contents(__DIR__ . '/favicon.ico'));

// ---------------------------------------------------------
// Register all Apps view paths: app/Apps/*/Views
// ---------------------------------------------------------
foreach (glob($basePath . '/app/Apps/*/Views', GLOB_ONLYDIR) as $viewsDir) {
    View::addPath($viewsDir);
}

// ---------------------------------------------------------
// Kernel + middleware pipeline and dispatch
// ---------------------------------------------------------
$kernel = new Kernel();
$kernel->push(new ErrorCatcher());
// $kernel->push(new TrailingSlash()); // TEMP disabled

$req = new Request();

$res = $kernel->handle($req, function (Request $r) use ($router, $uriPath): Response {
    $path = $uriPath;

    // Guest-only kezelést kivettük: az AuthRequired whitelisteli a /login-t.
    $auth = new Kernel();
    $auth->push(new AuthRequired());

    return $auth->handle($r, function (Request $rr) use ($router, $path): Response {
        $html = $router->dispatch($rr->method(), $path); // returns ?string

        if ($html === null) {
            if (!headers_sent()) {
                http_response_code(404);
                header('Content-Type: text/html; charset=utf-8');
            }
            $html = '<h1>404 Not Found</h1>';
        }

        return (new Response())->html($html);
    });
});

$res->send();
