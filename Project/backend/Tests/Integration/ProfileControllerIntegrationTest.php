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

        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository);

        $this->statisticsServiceMock = $this->createMock(StatisticsService::class);

        $this->controller = new ProfileController(
            $userService,
            $this->statisticsServiceMock
        );

        $this->pdo->exec("
            INSERT INTO users (name, email, password_hash, gender, birth_date)
            VALUES ('Константин', 'kostya@fit.com', 'password_hash_abc', 'male', '1988-12-10')
        ");

        $this->existingUserId = (int)$this->pdo->lastInsertId();
    }

    public function testIndexSuccessWhenAuthorized(): void
    {
        $this->statisticsServiceMock
            ->method('getProfileStatistics')
            ->with($this->existingUserId)
            ->willReturn([
                'parameters' => [
                    'weight' => 80.0,
                    'height' => 180,
                    'activityLevel' => 'moderate',
                    'goal' => 'maintain',
                    'measuredAt' => '2026-05-21 10:00:00',
                ],
                'dailyNorm' => [
                    'calories' => 2300,
                    'proteins' => 160.0,
                    'fats' => 75.0,
                    'carbs' => 260.0,
                    'createdAt' => '2026-05-21 10:00:00',
                ],
                'monthlyAverage' => [
                    'calories' => 2000,
                    'proteins' => 140.0,
                    'fats' => 65.0,
                    'carbs' => 230.0,
                ],
            ]);

        $request = (new ServerRequest('GET', '/profile'))
            ->withAttribute('user_id', $this->existingUserId);

        $response = $this->controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertEquals('Константин', $responseBody['name']);
        $this->assertEquals('kostya@fit.com', $responseBody['email']);
        $this->assertArrayHasKey('parameters', $responseBody);
        $this->assertArrayHasKey('dailyNorm', $responseBody);
        $this->assertArrayHasKey('monthlyAverage', $responseBody);
    }

    public function testIndexReturns401WhenUnauthorized(): void
    {
        $request = new ServerRequest('GET', '/profile');

        $response = $this->controller->index($request);

        $this->assertEquals(401, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertEquals('Пользователь не авторизован', $responseBody['error']);
    }

    public function testUpdateProfileInfoSuccess(): void
    {
        $rawBody = json_encode([
            'name' => 'Костя Новый',
            'email' => 'new_email@fit.com',
        ]);

        $request = (new ServerRequest('POST', '/profile/edit'))
            ->withBody(Utils::streamFor($rawBody))
            ->withAttribute('user_id', $this->existingUserId);

        $response = $this->controller->updateProfileInfo($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertEquals('Костя Новый', $responseBody['name']);
        $this->assertEquals('new_email@fit.com', $responseBody['email']);

        $stmt = $this->pdo->prepare("
            SELECT *
            FROM users
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $this->existingUserId,
        ]);

        $updatedDbUser = $stmt->fetch();

        $this->assertEquals('Костя Новый', $updatedDbUser['name']);
        $this->assertEquals('new_email@fit.com', $updatedDbUser['email']);
        $this->assertEquals('male', strtolower($updatedDbUser['gender']));
        $this->assertEquals('1988-12-10', $updatedDbUser['birth_date']);
    }

    public function testRecalculateNormsSuccess(): void
    {
        $rawBody = json_encode([
            'weight' => 85.5,
            'height' => 182,
            'activityLevel' => 1.375,
            'goal' => 'maintain',
        ]);

        $this->statisticsServiceMock
            ->expects($this->once())
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

    public function testRecalculateFailsOnMissingParameters(): void
    {
        $rawBody = json_encode([
            'weight' => 70,
            'activityLevel' => 1.2,
            'goal' => 'maintain',
        ]);

        $request = (new ServerRequest('POST', '/profile/recalculate'))
            ->withBody(Utils::streamFor($rawBody))
            ->withAttribute('user_id', $this->existingUserId);

        $response = $this->controller->recalculate($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertStringContainsString(
            'Необходимы параметры',
            $responseBody['error']
        );
    }
}