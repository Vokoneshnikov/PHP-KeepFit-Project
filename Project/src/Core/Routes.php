<?php

namespace App\Core;

class Routes {
    private static array $routes = [
        'GET' => [
            '/profile' => ['controller' => 'ProfileController', 'method' =>'index'],
        ],
        'POST' => [
            '/' => [],
        ],
    ];

    public static function getRoute(string $httpMethod, string $path) {
        return self::$routes[$httpMethod][$path];
    }
}
