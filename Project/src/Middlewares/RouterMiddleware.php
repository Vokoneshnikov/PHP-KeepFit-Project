<?php

namespace App\Middlewares;

use App\Core\RequestInfo;
use App\Core\Router;
class RouterMiddleware implements IMiddleware {
    public function __construct(private Router $router) {}

    public function handle(RequestInfo $request, ?IMiddleware $next) {
        
        $this->router->run();

        return;
    }

}