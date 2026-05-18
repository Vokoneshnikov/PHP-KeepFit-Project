<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\AuthController;
use App\Services\UserService;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class AuthControllerTest extends TestCase
{
    /**
     * Тест контроллера 1: Ошибка 400 Bad Request при неполных данных регистрации
     */
    public function testRegisterReturns400OnMissingFields()
    {
        $userServiceMock = $this->createMock(UserService::class);
        $controller = new AuthController($userServiceMock);

        // Посылаем запрос, забыв указать поле password
        $rawBody = json_encode(['email' => 'test@mail.ru', 'name' => 'Иван']);
        $request = (new ServerRequest('POST', '/api/register'))
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($rawBody));

        $response = $controller->register($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertArrayHasKey('error', $responseBody);
        $this->assertStringContainsString('Отсутствует или пустое обязательное поле', $responseBody['error']);
    }

    /**
     * Тест контроллера 2: Ошибка 400 при неверных учетных данных логина
     */
    public function testLoginReturns400OnInvalidCredentials()
    {
        $userServiceMock = $this->createMock(UserService::class);

        // Настраиваем мок так, будто логин вернул null (пользователь ввел неверный пароль)
        $userServiceMock->method('login')->willReturn(null);

        $controller = new AuthController($userServiceMock);

        $rawBody = json_encode(['email' => 'wrong@user.com', 'password' => 'fake_pass']);
        $request = (new ServerRequest('POST', '/api/login'))
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($rawBody));

        $response = $controller->login($request);

        $this->assertEquals(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertEquals('Неверный email или пароль', $responseBody['error']);
    }
}
