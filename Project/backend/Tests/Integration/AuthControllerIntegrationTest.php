<?php

namespace backend\Tests\Integration;

use App\Controllers\AuthController;
use App\Repositories\Implementations\UserRepository;
use App\Services\UserService;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Utils;

class AuthControllerIntegrationTest extends IntegrationTestCase
{
    private AuthController $controller;

    protected function setUp(): void
    {
        parent::setUp(); // Инициализируем изолированную БД в памяти

        // Собираем реальную цепочку зависимостей для аутентификации
        $userRepository = new UserRepository($this->pdo);

        // Предполагаем, что UserService принимает репозиторий в конструктор.
        // Если у тебя там есть еще и хэшер/токен-менеджер, добавь их моки при необходимости.
        $userService = new UserService($userRepository);

        $this->controller = new AuthController($userService);
    }

    /**
     * Тест: Успешная регистрация пользователя с валидными данными
     */
    public function testRegisterSuccessReturns201AndSavesToDb()
    {
        $rawBody = json_encode([
            'email' => 'integration_user@fit.com',
            'password' => 'secret_pass_123',
            'name' => 'Александр',
            'gender' => 'male',
            'birthDate' => '1992-08-15'
        ]);

        $request = (new ServerRequest('POST', '/api/register'))
            ->withBody(Utils::streamFor($rawBody));

        $response = $this->controller->register($request);

        // Проверяем статус ответа
        $this->assertEquals(201, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertEquals('Александр', $responseBody['name']);
        $this->assertEquals('integration_user@fit.com', $responseBody['email']);

        // Проверяем, что запись физически создалась в SQLite
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => 'integration_user@fit.com']);
        $dbUser = $stmt->fetch();

        $this->assertNotFalse($dbUser);
        $this->assertEquals('male', $dbUser['gender']);
        $this->assertEquals('1992-08-15', $dbUser['birth_date']);
    }

    /**
     * Тест: Ошибка 400, если отсутствует обязательное поле
     */
    public function testRegisterFailsWhenRequiredFieldIsMissing()
    {
        $rawBody = json_encode([
            'email' => 'bad_request@fit.com',
            // Поле 'password' умышленно пропущено
            'name' => 'Иван',
            'gender' => 'male',
            'birthDate' => '1995-01-01'
        ]);

        $request = (new ServerRequest('POST', '/api/register'))
            ->withBody(Utils::streamFor($rawBody));

        $response = $this->controller->register($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertStringContainsString('Отсутствует или пустое обязательное поле', $responseBody['error']);
    }

    /**
     * Тест: Ошибка 400 при передаче некорректного значения Gender
     */
    public function testRegisterFailsWithInvalidGender()
    {
        $rawBody = json_encode([
            'email' => 'gender_error@fit.com',
            'password' => 'password123',
            'name' => 'Тест',
            'gender' => 'Robot', // Недопустимое значение для Enum
            'birthDate' => '1995-01-01'
        ]);

        $request = (new ServerRequest('POST', '/api/register'))
            ->withBody(Utils::streamFor($rawBody));

        $response = $this->controller->register($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertStringContainsString('Передано недопустимое значение для поля gender', $responseBody['error']);
    }

    /**
     * Тест: Ошибка 400 при передаче невалидной строки даты
     */
    public function testRegisterFailsWithMalformedBirthDate()
    {
        $rawBody = json_encode([
            'email' => 'date_error@fit.com',
            'password' => 'password123',
            'name' => 'Тест',
            'gender' => 'female',
            'birthDate' => 'вчера днем' // Некорректный формат даты
        ]);

        $request = (new ServerRequest('POST', '/api/register'))
            ->withBody(Utils::streamFor($rawBody));

        $response = $this->controller->register($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertStringContainsString('Некорректный формат даты рождения', $responseBody['error']);
    }

    /**
     * Тест: Ошибка 400 при попытке входа с неверным паролем
     */
    public function testLoginFailsWithWrongCredentials()
    {
        // Вручную подселяем тестового пользователя в БД
        $this->pdo->exec("
            INSERT INTO users (name, email, password_hash, gender, birth_date) 
            VALUES ('Дмитрий', 'dima@fit.com', 'correct_hash', 'male', '1990-05-05')
        ");

        $rawBody = json_encode([
            'email' => 'dima@fit.com',
            'password' => 'wrong_password_xyz'
        ]);

        $request = (new ServerRequest('POST', '/api/login'))
            ->withBody(Utils::streamFor($rawBody));

        $response = $this->controller->login($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertEquals('Неверный email или пароль', $responseBody['error']);
    }
}
