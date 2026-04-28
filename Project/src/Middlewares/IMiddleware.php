<?php

namespace App\Middlewares;

use App\Core\RequestInfo;

interface IMiddleware
{
    public function handle(RequestInfo $request, ?IMiddleware $next);
}
