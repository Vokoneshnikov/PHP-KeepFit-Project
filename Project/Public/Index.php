<?php

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Router;
use GuzzleHttp\Psr7\ServerRequest;

$request = ServerRequest::fromGlobals();

$router = new Router();
$router->register([
    \App\Controllers\DiaryController::class,
    \App\Controllers\FoodController::class,
    \App\Controllers\FoodCustomController::class,
    \App\Controllers\MealController::class,
    \App\Controllers\ProfileController::class,
    \App\Controllers\StatisticsController::class,
]);
$response = $router->run($request);

http_response_code($response->getStatusCode());

foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}
echo $response->getBody();
