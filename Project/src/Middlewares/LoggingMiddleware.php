<?php

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();
        $startTime = microtime(true);

        $this->logger->info(">>> Входящий запрос: {$method} {$uri}", [
            'query' => $request->getQueryParams(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        $response = $handler->handle($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->logger->info("<<< Запрос обработан: {$method} {$uri}", [
            'status' => $response->getStatusCode(),
            'duration' => $duration . 'ms'
        ]);

        return $response;
    }
}
