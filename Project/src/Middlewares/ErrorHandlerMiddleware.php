<?php

namespace App\Middlewares;

use App\Core\RequestInfo;

class ErrorHandlerMiddleware implements IMiddleware
{
    public function handle(RequestInfo $request, ?IMiddleware $next)
    {
    }
}
