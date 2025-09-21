<?php
declare(strict_types=1);
use App\Apps\People\Controllers\PeopleController;

return function (\Core\Router $router): void {
    $router->get('/people', [PeopleController::class, 'index']);
    $router->get('/people/', [PeopleController::class, 'index']); // optional alias
};
