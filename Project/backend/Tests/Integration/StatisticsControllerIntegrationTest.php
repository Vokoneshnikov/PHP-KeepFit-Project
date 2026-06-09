<?php

namespace backend\Tests\Integration;

use App\Controllers\StatisticsController;
use App\Services\StatisticsService;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class StatisticsControllerIntegrationTest extends TestCase
{
    private StatisticsController $controller;
    private $statisticsServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Изолируем контроллер от базы данных, мокая StatisticsService
        $this->statisticsServiceMock = $this->createMock(StatisticsService::class);
        $this->controller = new StatisticsController($this->statisticsServiceMock);
    }

    public function testShowWeeklyStatsSuccess()
    {
        // Настраиваем возврат фейковых агрегированных данных
        $this->statisticsServiceMock->expects($this->once())
            ->method('getWeeklyData')
            ->with(1)
            ->willReturn([
                'summary' => [
                    'avg' => ['calories' => 2100, 'proteins' => 140.0, 'fats' => 70.0, 'carbs' => 220.0],
                    'target' => ['calories' => 2300, 'proteins' => 150.0, 'fats' => 75.0, 'carbs' => 240.0]
                ],
                'history' => []
            ]);

        // Эмулируем авторизованный запрос от user_id = 1
        $request = (new ServerRequest('GET', '/stats/weekly'))
            ->withAttribute('user_id', 1);

        $response = $this->controller->showWeeklyStats($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertArrayHasKey('summary', $responseBody);
        $this->assertArrayHasKey('history', $responseBody);
    }

    public function testShowMonthlyStatsSuccess()
    {
        $this->statisticsServiceMock->expects($this->once())
            ->method('getMonthlyData')
            ->with(1)
            ->willReturn([
                'summary' => [
                    'avg' => ['calories' => 2000],
                    'target' => ['calories' => 2300]
                ],
                'history' => []
            ]);

        $request = (new ServerRequest('GET', '/stats/monthly'))
            ->withAttribute('user_id', 1);

        $response = $this->controller->showMonthlyStats($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertEquals(2000, $responseBody['summary']['avg']['calories']);
    }

    public function testStatsReturns401WhenUnauthorized()
    {
        // Отправляем запрос без user_id в атрибутах
        $request = new ServerRequest('GET', '/stats/weekly');

        $response = $this->controller->showWeeklyStats($request);

        $this->assertEquals(401, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertEquals('Пользователь не авторизован', $responseBody['error']);
    }
}
