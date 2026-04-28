<?php

namespace App\Middlewares;

use App\Core\RequestInfo;

class LoggingMiddleware implements IMiddleware
{
    public function handle(RequestInfo $request, ?IMiddleware $next)
    {
    }
}
