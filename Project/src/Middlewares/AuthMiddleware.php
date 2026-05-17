<?php

namespace App\Middlewares;

use App\Core\RequestInfo;

class AuthMiddleware implements IMiddleware
{
    public function handle(RequestInfo $request, ?IMiddleware $next)
    {
    }
}
