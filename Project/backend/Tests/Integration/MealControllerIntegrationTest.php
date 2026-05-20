<?php

namespace backend\Tests\Integration;

use App\Controllers\MealController;
use App\Repositories\Implementations\FoodRepository;
use App\Repositories\Implementations\MealRepository;
use App\Services\MealService;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Utils;

class MealControllerIntegrationTest extends IntegrationTestCase
{
    private MealController $controller;
    private int $testUserId = 1;
    private int $testMealId;

    protected function setUp(): void
    {
        parent::setUp();

        $mealRepo = new MealRepository($this->pdo);
        $foodRepo = new FoodRepository($this->pdo);
        $mealService = new MealService($mealRepo, $foodRepo);

        $this->controller = new MealController($mealService);

        // Подготавливаем эталонные данные для тестов
        // 1. Юзер
        $this->pdo->exec("INSERT INTO users (name, email, password_hash, gender, birth_date) VALUES ('Тест', 'test@fit.com', '123', 'male', '1990-01-01')");
        // 2. Другой юзер (для проверки прав)
        $this->pdo->exec("INSERT INTO users (name, email, password_hash, gender, birth_date) VALUES ('Хакер', 'hacker@fit.com', '123', 'male', '1990-01-01')");
        // 3. Продукт
        $this->pdo->exec("INSERT INTO foods (name, calories, proteins, fats, carbs) VALUES ('Рис', 130, 2, 0, 28)");
        // 4. Запись в дневнике (принадлежит юзеру 1)
        $this->pdo->exec("INSERT INTO meals (user_id, food_id, amount_grams, meal_type, consumed_at) VALUES (1, 1, 100, 'lunch', '2026-05-20')");

        $this->testMealId = (int)$this->pdo->lastInsertId();
    }

    /**
     * Тест: Успешное получение записи дневника
     */
    public function testGetProductInfoSuccess()
    {
        $request = (new ServerRequest('GET', "/meals/{$this->testMealId}"))
            ->withAttribute('user_id', $this->testUserId);

        $response = $this->controller->getProductInfo($request, (string)$this->testMealId);

        $this->assertEquals(200, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertEquals(100, $responseBody['amountGrams']);
        $this->assertEquals('lunch', $responseBody['mealType']);
    }

    /**
     * Тест: Запрет на просмотр чужого приема пищи
     */
    public function testGetProductInfoForbiddenForOtherUser()
    {
        // Делаем запрос от имени юзера 2 (хакера) к записи юзера 1
        $request = (new ServerRequest('GET', "/meals/{$this->testMealId}"))
            ->withAttribute('user_id', 2);

        $response = $this->controller->getProductInfo($request, (string)$this->testMealId);

        // В MealController прописан явный возврат 403 при несовпадении ID
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Тест: Успешное обновление граммовки в дневнике
     */
    public function testUpdateProductInfoSuccess()
    {
        $rawBody = json_encode(['weight' => 250]); // Меняем 100г на 250г

        $request = (new ServerRequest('POST', "/meals/{$this->testMealId}"))
            ->withBody(Utils::streamFor($rawBody))
            ->withAttribute('user_id', $this->testUserId);

        $response = $this->controller->updateProductInfo($request, (string)$this->testMealId);

        $this->assertEquals(200, $response->getStatusCode());

        // Проверяем БД, что вес реально изменился
        $stmt = $this->pdo->query("SELECT amount_grams FROM meals WHERE id = {$this->testMealId}");
        $updatedWeight = (int)$stmt->fetchColumn();

        $this->assertEquals(250, $updatedWeight);
    }

    /**
     * Тест: Успешное удаление записи
     */
    public function testDeleteProductSuccess()
    {
        $request = (new ServerRequest('DELETE', "/meals/{$this->testMealId}"))
            ->withAttribute('user_id', $this->testUserId);

        $response = $this->controller->deleteProduct($request, (string)$this->testMealId);

        $this->assertEquals(200, $response->getStatusCode());

        // Проверяем БД, что записи больше нет
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM meals WHERE id = {$this->testMealId}");
        $count = (int)$stmt->fetchColumn();

        $this->assertEquals(0, $count);
    }
}
