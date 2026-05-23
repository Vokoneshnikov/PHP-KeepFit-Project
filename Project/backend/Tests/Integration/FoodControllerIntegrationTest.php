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
        parent::setUp();

        $foodRepo = new FoodRepository($this->pdo);
        $mealRepo = new MealRepository($this->pdo);

        $foodService = new FoodService($foodRepo);
        $mealService = new MealService($mealRepo, $foodRepo);

        $this->controller = new FoodController($foodService, $mealService);

        $this->pdo->exec("
            INSERT INTO users (name, email, password_hash, gender, birth_date)
            VALUES ('Тест', 'test@fit.com', '123', 'male', '1990-01-01')
        ");
    }

    public function testGetSearchMainPageReturnsDefaultFoods(): void
    {
        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs)
            VALUES ('Яблоко', 52, 0.3, 0.2, 14)
        ");

        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs)
            VALUES ('Банан', 89, 1.1, 0.3, 22)
        ");

        $request = new ServerRequest('GET', '/food');

        $response = $this->controller->getSearchMainPage($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertCount(2, $responseBody);
        $this->assertEquals('Яблоко', $responseBody[0]['name']);
        $this->assertEquals('Банан', $responseBody[1]['name']);
    }

    public function testSearchFoodsReturnsMatches(): void
    {
        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs)
            VALUES ('Яблоко зеленое', 52, 0.3, 0.2, 14)
        ");

        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs)
            VALUES ('Яблоко красное', 47, 0.4, 0.4, 10)
        ");

        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs)
            VALUES ('Банан', 89, 1.1, 0.3, 22)
        ");

        $request = (new ServerRequest('GET', '/food/search'))
            ->withQueryParams([
                'q' => 'Яблоко',
            ]);

        $response = $this->controller->getSearchResults($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertCount(2, $responseBody);
        $this->assertStringContainsString('Яблоко', $responseBody[0]['name']);
    }

    public function testSearchFoodsReturns400WhenQueryIsEmpty(): void
    {
        $request = (new ServerRequest('GET', '/food/search'))
            ->withQueryParams([
                'q' => '',
            ]);

        $response = $this->controller->getSearchResults($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertEquals('Поисковый запрос не может быть пустым', $responseBody['error']);
    }

    public function testGetRecentFoodReturns401WhenUnauthorized(): void
    {
        $request = new ServerRequest('GET', '/food/recent');

        $response = $this->controller->getRecentFood($request);

        $this->assertEquals(401, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertEquals('Пользователь не авторизован', $responseBody['error']);
    }

    public function testGetRecentFoodReturnsRecentFoods(): void
    {
        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs)
            VALUES ('Рис', 130, 2, 0, 28)
        ");

        $riceId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs)
            VALUES ('Гречка', 110, 4.2, 1.1, 21.3)
        ");

        $buckwheatId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("
            INSERT INTO meals (user_id, food_id, amount_grams, meal_type, consumed_at)
            VALUES (1, {$riceId}, 100, 'lunch', '2026-05-21')
        ");

        $this->pdo->exec("
            INSERT INTO meals (user_id, food_id, amount_grams, meal_type, consumed_at)
            VALUES (1, {$buckwheatId}, 150, 'dinner', '2026-05-21')
        ");

        $request = (new ServerRequest('GET', '/food/recent'))
            ->withAttribute('user_id', 1);

        $response = $this->controller->getRecentFood($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertCount(2, $responseBody);
        $this->assertEquals('Гречка', $responseBody[0]['name']);
        $this->assertEquals('Рис', $responseBody[1]['name']);
    }

    public function testGetCreateFormReturnsMetadata(): void
    {
        $request = new ServerRequest('GET', '/food/add');

        $response = $this->controller->getCreateForm($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertArrayHasKey('allowed_categories', $responseBody);
        $this->assertArrayHasKey('rules', $responseBody);
    }

    public function testStoreProductReturns201AndSavesToDb(): void
    {
        $rawBody = json_encode([
            'name' => 'Куриная грудка',
            'calories' => 113,
            'proteins' => 23.6,
            'fats' => 1.9,
            'carbs' => 0.4,
        ]);

        $request = (new ServerRequest('POST', '/food/add'))
            ->withBody(Utils::streamFor($rawBody))
            ->withAttribute('user_id', 1);

        $response = $this->controller->storeProduct($request);

        $this->assertEquals(201, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertArrayHasKey('id', $responseBody);
        $this->assertEquals('Куриная грудка', $responseBody['name']);

        $stmt = $this->pdo->query("
            SELECT *
            FROM foods
            WHERE id = " . $responseBody['id']
        );

        $dbFood = $stmt->fetch();

        $this->assertEquals(113, $dbFood['calories']);
        $this->assertEquals(1, $dbFood['created_by']);
    }

    public function testStoreProductReturns401WhenUnauthorized(): void
    {
        $request = (new ServerRequest('POST', '/food/add'))
            ->withBody(Utils::streamFor(json_encode([
                'name' => 'Яблоко',
                'calories' => 52,
            ])));

        $response = $this->controller->storeProduct($request);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testStoreProductReturns400WhenRequiredFieldIsMissing(): void
    {
        $rawBody = json_encode([
            'name' => 'Яблоко',
            'calories' => 52,
            'proteins' => 0.3,
            'fats' => 0.2,
            // carbs намеренно отсутствует
        ]);

        $request = (new ServerRequest('POST', '/food/add'))
            ->withBody(Utils::streamFor($rawBody))
            ->withAttribute('user_id', 1);

        $response = $this->controller->storeProduct($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertStringContainsString('Отсутствует обязательное поле продукта', $responseBody['error']);
    }

    public function testStoreProductReturns400WhenCpfcIsNegative(): void
    {
        $rawBody = json_encode([
            'name' => 'Некорректный продукт',
            'calories' => -10,
            'proteins' => 1,
            'fats' => 1,
            'carbs' => 1,
        ]);

        $request = (new ServerRequest('POST', '/food/add'))
            ->withBody(Utils::streamFor($rawBody))
            ->withAttribute('user_id', 1);

        $response = $this->controller->storeProduct($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertEquals('КБЖУ не может быть отрицательным', $responseBody['error']);
    }

    public function testGetProductPageReturnsProduct(): void
    {
        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs)
            VALUES ('Овсянка', 68, 2.5, 1.5, 12)
        ");

        $foodId = (int)$this->pdo->lastInsertId();

        $request = new ServerRequest('GET', "/food/{$foodId}");

        $response = $this->controller->getProductPage($request, (string)$foodId);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertEquals($foodId, $responseBody['id']);
        $this->assertEquals('Овсянка', $responseBody['name']);
    }

    public function testGetProductPageReturns400WhenProductNotFound(): void
    {
        $request = new ServerRequest('GET', '/food/999');

        $response = $this->controller->getProductPage($request, '999');

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertEquals('Продукт не найден', $responseBody['error']);
    }

    public function testAddFoodToMealLogReturns201(): void
    {
        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs)
            VALUES ('Овсянка', 68, 2.5, 1.5, 12)
        ");

        $foodId = (int)$this->pdo->lastInsertId();

        $rawBody = json_encode([
            'weight' => 200,
            'mealType' => 'breakfast',
        ]);

        $request = (new ServerRequest('POST', "/food/{$foodId}"))
            ->withBody(Utils::streamFor($rawBody))
            ->withAttribute('user_id', 1);

        $response = $this->controller->addProduct($request, (string)$foodId);

        $this->assertEquals(201, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertEquals(
            'Продукт успешно добавлен в дневник питания',
            $responseBody['message']
        );

        $stmt = $this->pdo->query("
            SELECT *
            FROM meals
            WHERE user_id = 1
        ");

        $meal = $stmt->fetch();

        $this->assertNotFalse($meal);
        $this->assertEquals(200, $meal['amount_grams']);
        $this->assertEquals('breakfast', $meal['meal_type']);
    }

    public function testAddProductReturns401WhenUnauthorized(): void
    {
        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs)
            VALUES ('Овсянка', 68, 2.5, 1.5, 12)
        ");

        $foodId = (int)$this->pdo->lastInsertId();

        $request = (new ServerRequest('POST', "/food/{$foodId}"))
            ->withBody(Utils::streamFor(json_encode([
                'weight' => 200,
                'mealType' => 'breakfast',
            ])));

        $response = $this->controller->addProduct($request, (string)$foodId);

        $this->assertEquals(401, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertEquals('Пользователь не авторизован', $responseBody['error']);
    }

    public function testAddProductReturns400WhenWeightIsMissing(): void
    {
        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs)
            VALUES ('Овсянка', 68, 2.5, 1.5, 12)
        ");

        $foodId = (int)$this->pdo->lastInsertId();

        $request = (new ServerRequest('POST', "/food/{$foodId}"))
            ->withBody(Utils::streamFor(json_encode([
                'mealType' => 'breakfast',
            ])))
            ->withAttribute('user_id', 1);

        $response = $this->controller->addProduct($request, (string)$foodId);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertStringContainsString('Необходимо указать корректный вес порции', $responseBody['error']);
    }

    public function testAddProductReturns400WhenWeightIsNegative(): void
    {
        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs)
            VALUES ('Овсянка', 68, 2.5, 1.5, 12)
        ");

        $foodId = (int)$this->pdo->lastInsertId();

        $request = (new ServerRequest('POST', "/food/{$foodId}"))
            ->withBody(Utils::streamFor(json_encode([
                'weight' => -10,
                'mealType' => 'breakfast',
            ])))
            ->withAttribute('user_id', 1);

        $response = $this->controller->addProduct($request, (string)$foodId);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testAddProductThrowsValueErrorWhenMealTypeIsInvalid(): void
    {
        $this->expectException(\ValueError::class);

        $this->pdo->exec("
        INSERT INTO foods (name, calories, proteins, fats, carbs)
        VALUES ('Овсянка', 68, 2.5, 1.5, 12)
    ");

        $foodId = (int)$this->pdo->lastInsertId();

        $request = (new ServerRequest('POST', "/food/{$foodId}"))
            ->withBody(Utils::streamFor(json_encode([
                'weight' => 200,
                'mealType' => 'wrong_type',
            ])))
            ->withAttribute('user_id', 1);

        $this->controller->addProduct($request, (string)$foodId);
    }
}