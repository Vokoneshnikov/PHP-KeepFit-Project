<?php

require_once __DIR__ . '/../config/bootstrap.php';
// use App\Core\Route;
// use App\Repositories\Implementations\UserRepository;
// use App\Dtos\Responses\UserResponse;
use App\Core\Router;

// $rep = new UserRepository();
// $users = $rep->getAll();

// foreach($users as $user) {
//     echo $user->name . "<br/>";
// }
$router = new Router();
$router->register([
    \App\Controllers\DiaryController::class,
    \App\Controllers\FoodController::class,
    \App\Controllers\FoodCustomController::class,
    \App\Controllers\MealController::class,
    \App\Controllers\ProfileController::class,
    \App\Controllers\StatisticsController::class,
    ]);
$router->run();
