<?php

namespace backend\Tests\Integration;

use App\Controllers\ProfileController;
use App\Dtos\Responses\DailyNormsResponse;
use App\Repositories\Implementations\UserRepository;
use App\Services\StatisticsService;
use App\Services\UserService;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Utils;

class ProfileControllerIntegrationTest extends IntegrationTestCase
{
    private ProfileController $controller;
    private $statisticsServiceMock;
    private int $existingUserId;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Инициализируем реальные компоненты для работы с профилем
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository);

        // 2. Изолируем StatisticsService с помощью мока, чтобы не зависеть от его внутренней логики
        $this->statisticsServiceMock = $this->createMock(StatisticsService::class);

        $this->controller = new ProfileController($userService, $this->statisticsServiceMock);

        // 3. Подселяем тестового пользователя в БД SQLite для работы тестов профиля
        $this->pdo->exec("
            INSERT INTO users (name, email, password_hash, gender, birth_date) 
            VALUES ('Константин', 'kostya@fit.com', 'password_hash_abc', 'male', '1988-12-10')
        ");
        $this->existingUserId = (int)$this->pdo->lastInsertId();
    }

    /**
     * Тест: Успешное получение данных профиля авторизованным пользователем
     */
    public function testIndexSuccessWhenAuthorized()
    {
        $request = (new ServerRequest('GET', '/profile'))
            ->withAttribute('user_id', $this->existingUserId);

        $response = $this->controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        // Проверяем, что вернулись корректные данные сущности/DTO
        $this->assertEquals('Константин', $responseBody['name']);
        $this->assertEquals('kostya@fit.com', $responseBody['email']);
    }

    /**
     * Тест: Ошибка 401 при попытке просмотра профиля без авторизации
     */
    public function testIndexReturns401WhenUnauthorized()
    {
        $request = new ServerRequest('GET', '/profile');
        // Атрибут 'user_id' не устанавливается

        $response = $this->controller->index($request);

        $this->assertEquals(401, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertEquals('Пользователь не авторизован', $responseBody['error']);
    }

    /**
     * Тест: Успешное частичное обновление данных профиля
     */
    public function testUpdateProfileInfoSuccess()
    {
        $rawBody = json_encode([
            'weight' => 85.5,
            'height' => 182,
            'activityLevel' => 1.375,
            'goal' => 'maintain'
        ]);

        $request = (new ServerRequest('POST', '/profile/edit'))
            ->withBody(Utils::streamFor($rawBody))
            ->withAttribute('user_id', $this->existingUserId);

        $response = $this->controller->updateProfileInfo($request);

        $this->assertEquals(200, $response->getStatusCode());

        // Проверяем изменения непосредственно в базе данных
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $this->existingUserId]);
        $updatedDbUser = $stmt->fetch();

        $this->assertEquals('Костя Новый', $updatedDbUser['name']);
        $this->assertEquals('new_email@fit.com', $updatedDbUser['email']);
        // Проверяем, что старые данные остались нетронутыми
        $this->assertEquals('male', $updatedDbUser['gender']);
        $this->assertEquals('1988-12-10', $updatedDbUser['birth_date']);
    }

    /**
     * Тест: Успешный пересчет норм КБЖУ через StatisticsService
     */
    public function testRecalculateNormsSuccess()
    {
        $rawBody = json_encode([
            'weight' => 85.5,
            'height' => 182,
            'activityLevel' => 1.375
        ]);

        $this->statisticsServiceMock->expects($this->once())
            ->method('calculateAndSaveNorms')
            ->willReturn(new DailyNormsResponse(
                userId: $this->existingUserId,
                dailyCalories: 2450,
                proteins: 160.0,
                fats: 80.0,
                carbs: 270.0
            ));

        $request = (new ServerRequest('POST', '/profile/recalculate'))
            ->withBody(Utils::streamFor($rawBody))
            ->withAttribute('user_id', $this->existingUserId);

        $response = $this->controller->recalculate($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertEquals('Норма КБЖУ успешно пересчитана', $responseBody['message']);
        $this->assertArrayHasKey('norm', $responseBody);
        $this->assertEquals(2450, $responseBody['norm']['dailyCalories']);
    }

    /**
     * Тест: Ошибка 400 при пересчете норм, если переданы не все параметры физиологии
     */
    public function testRecalculateFailsOnMissingParameters()
    {
        $rawBody = json_encode([
            'weight' => 70,
            // height умышленно пропущен
            'activityLevel' => 1.2
        ]);

        $request = (new ServerRequest('POST', '/profile/recalculate'))
            ->withBody(Utils::streamFor($rawBody))
            ->withAttribute('user_id', $this->existingUserId);

        $response = $this->controller->recalculate($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertStringContainsString('Необходимы параметры: weight, height, activityLevel, goal', $responseBody['error']);
    }
}
