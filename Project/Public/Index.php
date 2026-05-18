<?php

require_once __DIR__ . '/../config/bootstrap.php';

use App\Controllers\DiaryController;
use App\Controllers\FoodController;
use App\Controllers\FoodCustomController;
use App\Controllers\MealController;
use App\Controllers\ProfileController;
use App\Controllers\StatisticsController;
use App\Core\Router;
use App\Repositories\Implementations\FoodRepository;
use App\Repositories\Implementations\MealRepository;
use App\Repositories\Implementations\UserRepository;
use App\Repositories\Interfaces\IFoodRepository;
use App\Repositories\Interfaces\IMealRepository;
use App\Repositories\Interfaces\IUserRepository;
use GuzzleHttp\Psr7\ServerRequest;
use App\Middlewares\LoggingMiddleware;


$request = ServerRequest::fromGlobals();

/** @var \App\Core\DIContainer $container */
$container->bind(IFoodRepository::class, FoodRepository::class);
$container->bind(IMealRepository::class, MealRepository::class);
$container->bind(IUserRepository::class, UserRepository::class);

$router = new Router($container);
$router->register([
    DiaryController::class,
    FoodController::class,
    FoodCustomController::class,
    MealController::class,
    ProfileController::class,
    StatisticsController::class,
]);
$router->addMiddleware($container->get(LoggingMiddleware::class));
$response = $router->run($request);

http_response_code($response->getStatusCode());

foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}

echo $response->getBody();
