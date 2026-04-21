<?php

namespace App\Core;

use App\Controllers;
use ReflectionClass;

class Router {
    private static $routes = [];
    public function __construct() {}

    public function register(array $controllers) {
        
    foreach($controllers as $controller) {

        $reflector = new ReflectionClass($controller::class);

        $methods = $reflector->getMethods();

        foreach ($methods as $method) {

            $attributes = $method->getAttributes(Route::class);

            foreach($attributes as $attribure) {

                $route = $attribure->getInstance();
                $httpMethod = $route->httpMethod;
                $path = $route->path;

                $this->routes[strtoupper($httpMethod)][$path] = [
                    'controller' => $controller,
                    'method' => $method,
                ];
                }
            }
        }
    }
    public function run() {
        $requestInfo = new requestInfo();

        $httpMethod = strtoupper($requestInfo->method);
        $path = $requestInfo->path;

        try {
            $route = $this->getRoute($path, $httpMethod);
            
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
    private function getRoute($path, $httpMethod) {
        return $this->routes[$httpMethod][$path];
    }
    private function sendNotFound(): void {
        header("HTTP/1.0 404 Not Found");
        echo "404 - Страница не найдена";
    }
}