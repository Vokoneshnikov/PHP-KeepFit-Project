<?php

namespace backend\Tests\Unit;

use App\Middlewares\AuthMiddleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddlewareTest extends TestCase
{
    public function testPublicRegisterRoutePassesWithoutToken(): void
    {
        $middleware = new AuthMiddleware();

        $request = new ServerRequest('POST', '/api/register');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], 'public route passed');
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('public route passed', (string)$response->getBody());
    }

    public function testPublicLoginRoutePassesWithoutToken(): void
    {
        $middleware = new AuthMiddleware();

        $request = new ServerRequest('POST', '/api/login');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], 'login route passed');
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('login route passed', (string)$response->getBody());
    }

    public function testProtectedRouteReturns401WhenAuthorizationHeaderMissing(): void
    {
        $middleware = new AuthMiddleware();

        $request = new ServerRequest('GET', '/profile');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals('Missing or malformed access token', $body['error']);
    }

    public function testProtectedRouteReturns401WhenAuthorizationHeaderMalformed(): void
    {
        $middleware = new AuthMiddleware();

        $request = (new ServerRequest('GET', '/profile'))
            ->withHeader('Authorization', 'wrong-token-format');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals('Missing or malformed access token', $body['error']);
    }

    public function testProtectedRouteReturns401WhenTokenHasInvalidStructure(): void
    {
        $middleware = new AuthMiddleware();

        $request = (new ServerRequest('GET', '/profile'))
            ->withHeader('Authorization', 'Bearer invalid-token');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals('Invalid or expired access token', $body['error']);
    }

    public function testProtectedRouteReturns401WhenTokenSignatureIsInvalid(): void
    {
        $middleware = new AuthMiddleware();

        $token = $this->createJwt([
            'sub' => 5,
            'email' => 'test@fit.com',
            'exp' => time() + 3600,
        ], 'wrong_secret');

        $request = (new ServerRequest('GET', '/profile'))
            ->withHeader('Authorization', 'Bearer ' . $token);

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals('Invalid or expired access token', $body['error']);
    }

    public function testProtectedRouteReturns401WhenTokenIsExpired(): void
    {
        $middleware = new AuthMiddleware();

        $token = $this->createJwt([
            'sub' => 5,
            'email' => 'test@fit.com',
            'exp' => time() - 3600,
        ]);

        $request = (new ServerRequest('GET', '/profile'))
            ->withHeader('Authorization', 'Bearer ' . $token);

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals('Invalid or expired access token', $body['error']);
    }

    public function testProtectedRoutePassesWhenTokenIsValidAndAddsUserIdAttribute(): void
    {
        $middleware = new AuthMiddleware();

        $token = $this->createJwt([
            'sub' => 7,
            'email' => 'test@fit.com',
            'role' => 'User',
            'exp' => time() + 3600,
        ]);

        $request = (new ServerRequest('GET', '/profile'))
            ->withHeader('Authorization', 'Bearer ' . $token);

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    json_encode([
                        'user_id' => $request->getAttribute('user_id'),
                    ])
                );
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals(7, $body['user_id']);
    }

    private function createJwt(array $payload, string $secret = 'super_secret_key_123'): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            $base64UrlHeader . '.' . $base64UrlPayload,
            $secret,
            true
        );

        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }

    private function base64UrlEncode(string $data): string
    {
        return str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode($data)
        );
    }
}