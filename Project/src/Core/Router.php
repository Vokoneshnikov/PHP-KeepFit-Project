<?php

namespace App\Core;

use ReflectionClass;

class Router {
    private static $routes = [];
    private DIContainer $container;
    
    public function __construct(?DIContainer $container = null) {
        $this->container = $container ?? new DIContainer();
    }

    public function register(array $controllers) {
        
        foreach($controllers as $controllerName) {

            $reflector = new ReflectionClass($controllerName);

            $methods = $reflector->getMethods();

            foreach ($methods as $method) {
                $attributes = $method->getAttributes(Route::class);
                
                if (empty($attributes)) {
                    continue; 
                }

                $route = $attributes[0]->newInstance();
            
                $httpMethods = $route->httpMethods;
                $path = $route->path;
                
                foreach($httpMethods as $httpMethod) {
                    $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path); 
                    
                    $pattern = "#^" . $pattern . "$#sD";

                    self::$routes[strtoupper($httpMethod)][$pattern] = [
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
        $path = parse_url($requestInfo->path, PHP_URL_PATH);

        $routesForHttpMethod = self::$routes[$httpMethod] ?? [];

        foreach($routesForHttpMethod as $pattern => $handler) {
            if (preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                try {
                    $controller = $this->container->get($handler['controller']);
                    $method = $handler['method'];

                    return call_user_func_array([$controller, $method], $params);
                }
                catch (\Exception $e) {
                    return $this->sendNotFound();
                }
            }
        }
        return $this->sendNotFound();
    }
    private function sendNotFound(): void {
        header("HTTP/1.0 404 Not Found");
        echo "404 - Страница не найдена";
    }
}
