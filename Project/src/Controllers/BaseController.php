<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;

abstract class BaseController
{
    // Быстрая сборка успешного JSON-ответа
    protected function json(mixed $data, int $status = 200): ResponseInterface
    {
        return new Response(
            $status,
            ['Content-Type' => 'application/json'],
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
    }

    // Единый формат для ошибок валидации и сбоев (везде 400, как договорились)
    protected function error(string $message, int $status = 400): ResponseInterface
    {
        return $this->json(['error' => $message], $status);
    }

    // Безопасное извлечение ассоциативного массива из тела JSON-запроса
    protected function getJsonBody(ServerRequestInterface $request): array
    {
        $body = $request->getBody()->getRemainingContents();
        if (empty($body)) {
            $body = (string)$request->getParsedBody();
        }

        $data = json_decode($body, true);
        return is_array($data) ? $data : [];
    }
}
