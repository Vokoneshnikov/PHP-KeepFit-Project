<?php

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Router;
use GuzzleHttp\Psr7\ServerRequest;
use App\Middlewares\LoggingMiddleware;


$request = ServerRequest::fromGlobals();

/** @var \App\Core\DIContainer $container */
$router = new Router($container);
$router->register([
    \App\Controllers\DiaryController::class,
    \App\Controllers\FoodController::class,
    \App\Controllers\FoodCustomController::class,
    \App\Controllers\MealController::class,
    \App\Controllers\ProfileController::class,
    \App\Controllers\StatisticsController::class,
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
