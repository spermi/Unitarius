<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Core/helpers.php';

use Core\ErrorHandler;
use Core\Router;
use App\Controllers\HomeController;

// Optional: load .env if available (Dotenv installed in composer)
$basePath = dirname(__DIR__);
$envFile  = $basePath . '/.env';
if (is_file($envFile)) {
    try {
        (Dotenv\Dotenv::createImmutable($basePath))->load();
    } catch (Throwable) {
        // ignore env loading error in minimal setup
    }
}

// Determine environment (default: production)
$appEnv = $_ENV['APP_ENV'] ?? 'production';
ErrorHandler::register($appEnv);

// --- app bootstrap below ---
$router = new Router();
$router->get('/', [HomeController::class, 'index']);
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

// public/index.php (végére, csak ideiglenes teszt miatt!)
use App\Controllers\HelloController;
use Core\{Request, Response, Kernel};
use Http\Middleware\{ErrorCatcher, TrailingSlash};

$req = new Request();
if (str_starts_with($req->uri(), '/unitarius/hello')) {
    $kernel = new Kernel();
    $kernel->push(new ErrorCatcher());
    $kernel->push(new TrailingSlash());

    $res = $kernel->handle($req, fn($r) => (new HelloController())->greet($r));
    $res->send();
    exit;
}
