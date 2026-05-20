<?php

namespace backend\Tests\Integration;

use App\Controllers\FoodController;
use App\Repositories\Implementations\FoodRepository;
use App\Repositories\Implementations\MealRepository;
use App\Services\FoodService;
use App\Services\MealService;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Utils;

class FoodControllerIntegrationTest extends IntegrationTestCase
{
    private FoodController $controller;

    protected function setUp(): void
    {
        parent::setUp(); // Инициализируем БД SQLite в памяти

        // Собираем зависимости
        $foodRepo = new FoodRepository($this->pdo);
        $mealRepo = new MealRepository($this->pdo);
        $foodService = new FoodService($foodRepo);
        $mealService = new MealService($mealRepo, $foodRepo);

        $this->controller = new FoodController($foodService, $mealService);

        // Создаем базового юзера для внешних ключей
        $this->pdo->exec("INSERT INTO users (name, email, password_hash, gender, birth_date) VALUES ('Тест', 'test@fit.com', '123', 'male', '1990-01-01')");
    }

    /**
     * Тест: Успешное создание продукта авторизованным пользователем
     */
    public function testStoreProductReturns201AndSavesToDb()
    {
        $rawBody = json_encode([
            'name' => 'Куриная грудка',
            'calories' => 113,
            'proteins' => 23.6,
            'fats' => 1.9,
            'carbs' => 0.4
        ]);

        // Имитируем запрос с установленным user_id (как если бы отработало middleware)
        $request = (new ServerRequest('POST', '/food/add'))
            ->withBody(Utils::streamFor($rawBody))
            ->withAttribute('user_id', 1);

        $response = $this->controller->storeProduct($request);

        // Проверяем HTTP ответ
        $this->assertEquals(201, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertArrayHasKey('id', $responseBody);
        $this->assertEquals('Куриная грудка', $responseBody['name']);

        // Проверяем физически в БД, что продукт появился
        $stmt = $this->pdo->query("SELECT * FROM foods WHERE id = " . $responseBody['id']);
        $dbFood = $stmt->fetch();
        $this->assertEquals(113, $dbFood['calories']);
        $this->assertEquals(1, $dbFood['created_by']);
    }

    /**
     * Тест: Ошибка 401, если пользователь не авторизован (нет user_id)
     */
    public function testStoreProductReturns401WhenUnauthorized()
    {
        $request = (new ServerRequest('POST', '/food/add'))
            ->withBody(Utils::streamFor(json_encode(['name' => 'Яблоко', 'calories' => 52])));
        // НЕ устанавливаем ->withAttribute('user_id')

        $response = $this->controller->storeProduct($request);

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Тест: Поиск возвращает результаты (GET /food/search?q=...)
     */
    public function testSearchFoodsReturnsMatches()
    {
        // Заполняем БД продуктами
        $this->pdo->exec("INSERT INTO foods (name, calories, proteins, fats, carbs) VALUES ('Яблоко зеленое', 52, 0.3, 0.2, 14)");
        $this->pdo->exec("INSERT INTO foods (name, calories, proteins, fats, carbs) VALUES ('Яблоко красное', 47, 0.4, 0.4, 10)");
        $this->pdo->exec("INSERT INTO foods (name, calories, proteins, fats, carbs) VALUES ('Банан', 89, 1.1, 0.3, 22)");

        // Эмулируем query параметры
        $request = (new ServerRequest('GET', '/food/search'))
            ->withQueryParams(['q' => 'Яблоко']);

        $response = $this->controller->getSearchResults($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertCount(2, $responseBody); // Должно найтись два яблока
        $this->assertStringContainsString('Яблоко', $responseBody[0]['name']);
    }

    /**
     * Тест: Добавление существующего продукта в дневник
     */
    public function testAddFoodToMealLogReturns201()
    {
        // Создаем продукт
        $this->pdo->exec("INSERT INTO foods (name, calories, proteins, fats, carbs) VALUES ('Овсянка', 68, 2.5, 1.5, 12)");
        $foodId = $this->pdo->lastInsertId();

        $rawBody = json_encode([
            'weight' => 200, // 200 грамм
            'mealType' => 'breakfast'
        ]);

        $request = (new ServerRequest('POST', "/food/{$foodId}"))
            ->withBody(Utils::streamFor($rawBody))
            ->withAttribute('user_id', 1);

        $response = $this->controller->addProduct($request, (string)$foodId);

        $this->assertEquals(201, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertEquals('Продукт успешно добавлен в дневник питания', $responseBody['message']);

        // Проверяем, что в таблице meals появилась запись
        $stmt = $this->pdo->query("SELECT * FROM meals WHERE user_id = 1");
        $meal = $stmt->fetch();
        $this->assertNotFalse($meal);
        $this->assertEquals(200, $meal['amount_grams']);
        $this->assertEquals('breakfast', $meal['meal_type']);
    }
}
