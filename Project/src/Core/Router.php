<?php

namespace App\Core;

use App\Controllers;

class Router {

    public function __construct(private Routes $routes) {

    }

    public function run() {
        $requestInfo = new requestInfo();

        $httpMethod = $requestInfo->method;
        $path = $requestInfo->path;

        try {
            $route = $this->routes->getRoute($httpMethod, $path);
            
            $controllerName = "App\\Controllers\\" . $route['controller'];
            $controller = new $controllerName();

            $method = $route['method'];

            $controller->setRequestInfo($requestInfo);

            $controller->$method();
        }
        catch (\Exception $e){
            $this->sendNotFound();

        }
    }

    private function sendNotFound(): void {
        header("HTTP/1.0 404 Not Found");
        echo "404 - Страница не найдена";
    }
}