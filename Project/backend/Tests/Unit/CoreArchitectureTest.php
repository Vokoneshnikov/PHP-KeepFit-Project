<?php

namespace backend\Tests\Unit;

use App\Core\DIContainer;
use App\Core\RequestHandler;
use App\Core\Route;
use App\Core\Router;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CoreArchitectureTest extends TestCase
{
    public function testDiContainerReturnsManuallySetInstance(): void
    {
        $container = new DIContainer();

        $service = new TestSimpleService();

        $container->set(TestSimpleService::class, $service);

        $this->assertSame($service, $container->get(TestSimpleService::class));
    }

    public function testDiContainerResolvesClassWithoutConstructor(): void
    {
        $container = new DIContainer();

        $service = $container->get(TestSimpleService::class);

        $this->assertInstanceOf(TestSimpleService::class, $service);
    }

    public function testDiContainerResolvesClassWithDependency(): void
    {
        $container = new DIContainer();

        $service = $container->get(TestServiceWithDependency::class);

        $this->assertInstanceOf(TestServiceWithDependency::class, $service);
        $this->assertInstanceOf(TestSimpleService::class, $service->dependency);
    }

    public function testDiContainerUsesBinding(): void
    {
        $container = new DIContainer();

        $container->bind(TestServiceInterface::class, TestBoundService::class);

        $service = $container->get(TestServiceInterface::class);

        $this->assertInstanceOf(TestBoundService::class, $service);
    }

    public function testDiContainerThrowsExceptionForPrimitiveConstructorParameter(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('без типа или примитив');

        $container = new DIContainer();

        $container->get(TestServiceWithPrimitiveDependency::class);
    }

    public function testRequestHandlerCallsFallbackWhenNoMiddleware(): void
    {
        $request = new ServerRequest('GET', '/test');

        $handler = new RequestHandler([], function (ServerRequestInterface $request): ResponseInterface {
            return new Response(200, [], 'fallback');
        });

        $response = $handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('fallback', (string)$response->getBody());
    }

    public function testRequestHandlerProcessesMiddlewareBeforeFallback(): void
    {
        $request = new ServerRequest('GET', '/test');

        $middleware = new TestMiddleware();

        $handler = new RequestHandler([$middleware], function (ServerRequestInterface $request): ResponseInterface {
            $value = $request->getAttribute('from_middleware');

            return new Response(200, [], (string)$value);
        });

        $response = $handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('yes', (string)$response->getBody());
    }

    public function testRouterReturnsControllerResponseForRegisteredRoute(): void
    {
        $container = new DIContainer();
        $router = new Router($container);

        $router->register([
            TestRouteController::class,
        ]);

        $request = new ServerRequest('GET', '/test-route');

        $response = $router->run($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('route works', (string)$response->getBody());
    }

    public function testRouterPassesRouteParameterToController(): void
    {
        $container = new DIContainer();
        $router = new Router($container);

        $router->register([
            TestRouteController::class,
        ]);

        $request = new ServerRequest('GET', '/test-route/15');

        $response = $router->run($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('id:15', (string)$response->getBody());
    }

    public function testRouterReturns404WhenRouteNotFound(): void
    {
        $container = new DIContainer();
        $router = new Router($container);

        $router->register([
            TestRouteController::class,
        ]);

        $request = new ServerRequest('GET', '/missing-route');

        $response = $router->run($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('404 - Страница не найдена', (string)$response->getBody());
    }
}

class TestSimpleService
{
}

interface TestServiceInterface
{
}

class TestBoundService implements TestServiceInterface
{
}

class TestServiceWithDependency
{
    public function __construct(
        public readonly TestSimpleService $dependency
    ) {
    }
}

class TestServiceWithPrimitiveDependency
{
    public function __construct(
        public readonly string $name
    ) {
    }
}

class TestMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('from_middleware', 'yes');

        return $handler->handle($request);
    }
}

class TestRouteController
{
    #[Route('/test-route', ['GET'])]
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], 'route works');
    }

    #[Route('/test-route/{id}', ['GET'])]
    public function show(ServerRequestInterface $request, string $id): ResponseInterface
    {
        return new Response(200, [], 'id:' . $id);
    }
}