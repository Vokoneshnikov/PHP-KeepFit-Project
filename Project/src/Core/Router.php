<?php

namespace App\Core;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionClass;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

class Router
{
    private static $routes = [];
    private array $middlewares = [];
    private DIContainer $container;

    public function __construct(?DIContainer $container = null)
    {
        $this->container = $container ?? new DIContainer();
    }

    public function addMiddleware(MiddlewareInterface $middleware)
    {
        $this->middlewares[] = $middleware;
    }

    public function register(array $controllers): void
    {

        foreach ($controllers as $controllerName) {
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

                foreach ($httpMethods as $httpMethod) {
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

    public function run(ServerRequestInterface $request): ResponseInterface
    {
        $httpMethod = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();

        $routesForHttpMethod = self::$routes[$httpMethod] ?? [];

        foreach ($routesForHttpMethod as $pattern => $handler) {
            if (preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $fallbackHandler = fn(ServerRequestInterface $req) => $this->callController($req, $handler, $params);

                $requestHandler = new RequestHandler($this->middlewares, $fallbackHandler);
                try {
                    return $requestHandler->handle($request);
                } catch (\Exception $e) {
                    return $this->generateNotFoundResponse();
                }
            }
        }
        return $this->generateNotFoundResponse();
    }

    private function callController(ServerRequestInterface $request, array $handler, array $params): ResponseInterface
    {
        $controller = $this->container->get($handler['controller']);
        $method = $handler['method'];

        return call_user_func_array([$controller, $method], [$request, ...$params]);
    }

    private function generateNotFoundResponse(): ResponseInterface
    {
        return new Response(404, [], "404 - Страница не найдена");
    }
}
