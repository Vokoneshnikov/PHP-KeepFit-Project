<?php

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7\Response;
use App\Core\Config;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = strtoupper($request->getMethod());

        // Публичные маршруты, где токен не нужен
        $publicRoutes = [
            ['POST', '/api/register'],
            ['POST', '/api/login'],
        ];

        foreach ($publicRoutes as [$publicMethod, $publicPath]) {
            if ($method === $publicMethod && $path === $publicPath) {
                return $handler->handle($request);
            }
        }
        $authHeader = $request->getHeaderLine('Authorization');

        // Проверяем формат заголовка "Bearer {token}"
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->jsonErrorResponse('Missing or malformed access token', 401);
        }

        $token = $matches[1];
        $userData = $this->validateToken($token);

        if (!$userData) {
            return $this->jsonErrorResponse('Invalid or expired access token', 401);
        }

        $request = $request->withAttribute('user_id', $userData['sub']);

        return $handler->handle($request);
    }

    private function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$base64UrlHeader, $base64UrlPayload, $base64UrlSignature] = $parts;

        $secret = Config::get('JWT_SECRET', 'super_secret_key_123');
        $signature = $this->base64UrlDecode($base64UrlSignature);

        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);

        if (!hash_equals($signature, $expectedSignature)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($base64UrlPayload), true);

        if (!is_array($payload)) {
            return null;
        }
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }

    private function jsonErrorResponse(string $message, int $status): ResponseInterface
    {
        return new Response(
            $status,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => $message], JSON_UNESCAPED_UNICODE)
        );
    }
}