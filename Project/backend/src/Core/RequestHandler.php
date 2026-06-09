<?php

namespace App\Core;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandler implements RequestHandlerInterface
{
    private int $index = 0;


    public function __construct(
        private array $middleware,
        private \Closure $fallbackHandler
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->index >= count($this->middleware)) {
            return ($this->fallbackHandler)($request);
        }

        $middleware = $this->middleware[$this->index];
        $this->index++;

        return $middleware->process($request, $this);
    }
}
