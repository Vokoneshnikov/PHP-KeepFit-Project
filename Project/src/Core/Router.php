<?php

namespace App\Core;

use ReflectionClass;
use App\Core\DIContainer;
class Router {
    private static $routes = [];
    // private DIContainer $container = new DIContainer();
    public function __construct(private ?DIContainer $container = null) {
        $container = ($container) ?? new DIContainer();
    }

    public function register(array $controllers) {
        
    foreach($controllers as $controllerName) {

        $reflector = new ReflectionClass($controllerName);

        $methods = $reflector->getMethods();

        foreach ($methods as $method) {

            $route = $method->getAttributes(Route::class)[0]->newInstance();
        
            $httpMethods = $route->httpMethods;
            $path = $route->path;
            
            foreach($httpMethods as $httpMethod) {

                self::$routes[strtoupper($httpMethod)][$path] = [
                    'controller' => $controllerName,
                    'method' => $method->getName(),
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