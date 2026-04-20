<?php

require_once __DIR__ . '/../config/bootstrap.php';
use App\Repositories\Implementations\UserRepository;
use App\Dtos\Responses\UserResponse;

$rep = new UserRepository();
$users = $rep->getAll();

foreach($users as $user) {
    echo $user->name . "<br/>";
}

echo "Приложение работает! <br/>";
