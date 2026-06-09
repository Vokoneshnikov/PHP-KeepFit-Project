<?php

namespace backend\Tests\Integration;

use App\Dtos\Requests\CreateFoodRequest;
use App\Dtos\Requests\UpdateFoodRequest;
use App\Repositories\Implementations\FoodRepository;

class FoodRepositoryIntegrationTest extends IntegrationTestCase
{
    private FoodRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new FoodRepository($this->pdo);

        $this->pdo->exec("
            INSERT INTO users (name, email, password_hash, gender, birth_date)
            VALUES ('Тест', 'test@fit.com', '123', 'male', '1990-01-01')
        ");

        $this->pdo->exec("
            INSERT INTO users (name, email, password_hash, gender, birth_date)
            VALUES ('Другой', 'other@fit.com', '123', 'female', '1995-01-01')
        ");
    }

    public function testSaveCreateFoodSuccess(): void
    {
        $request = new CreateFoodRequest(
            name: 'Куриная грудка',
            calories: 113.0,
            proteins: 23.6,
            fats: 1.9,
            carbs: 0.4,
            createdBy: 1
        );

        $result = $this->repository->save($request);

        $this->assertEquals('Куриная грудка', $result->name);
        $this->assertEquals(113.0, $result->calories);
        $this->assertEquals(1, $result->createdBy);

        $stmt = $this->pdo->prepare("
            SELECT *
            FROM foods
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $result->id,
        ]);

        $dbFood = $stmt->fetch();

        $this->assertEquals('Куриная грудка', $dbFood['name']);
        $this->assertEquals(1, (int)$dbFood['created_by']);
    }

    public function testGetByIdSuccess(): void
    {
        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs, created_by)
            VALUES ('Яблоко', 52, 0.3, 0.2, 14, 1)
        ");

        $foodId = (int)$this->pdo->lastInsertId();

        $food = $this->repository->getById($foodId);

        $this->assertEquals($foodId, $food->id);
        $this->assertEquals('Яблоко', $food->name);
        $this->assertEquals(52.0, $food->calories);
        $this->assertEquals(1, $food->createdBy);
    }

    public function testGetByIdThrowsExceptionWhenFoodNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Продукт не найден');

        $this->repository->getById(999);
    }

    public function testGetAllReturnsAllFoods(): void
    {
        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs)
            VALUES ('Яблоко', 52, 0.3, 0.2, 14)
        ");

        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs)
            VALUES ('Банан', 89, 1.1, 0.3, 22)
        ");

        $foods = $this->repository->getAll();

        $this->assertCount(2, $foods);
        $this->assertEquals('Яблоко', $foods[0]->name);
        $this->assertEquals('Банан', $foods[1]->name);
    }

    public function testSearchReturnsMatchingFoods(): void
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

        $foods = $this->repository->search('Яблоко');

        $this->assertCount(2, $foods);
        $this->assertStringContainsString('Яблоко', $foods[0]->name);
        $this->assertStringContainsString('Яблоко', $foods[1]->name);
    }

    public function testSaveUpdateFoodSuccess(): void
    {
        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs, created_by)
            VALUES ('Старое название', 100, 5, 2, 10, 1)
        ");

        $foodId = (int)$this->pdo->lastInsertId();

        $request = new UpdateFoodRequest(
            id: $foodId,
            name: 'Новое название',
            proteins: 10.0,
            fats: 3.0,
            carbs: 20.0,
            calories: 150.0
        );

        $updatedFood = $this->repository->save($request);

        $this->assertEquals($foodId, $updatedFood->id);
        $this->assertEquals('Новое название', $updatedFood->name);
        $this->assertEquals(150.0, $updatedFood->calories);
        $this->assertEquals(10.0, $updatedFood->proteins);
        $this->assertEquals(3.0, $updatedFood->fats);
        $this->assertEquals(20.0, $updatedFood->carbs);
    }

    public function testDeleteReturnsTrueWhenFoodDeleted(): void
    {
        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs)
            VALUES ('Удаляемый продукт', 100, 1, 1, 1)
        ");

        $foodId = (int)$this->pdo->lastInsertId();

        $result = $this->repository->delete($foodId);

        $this->assertTrue($result);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM foods
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $foodId,
        ]);

        $this->assertEquals(0, (int)$stmt->fetchColumn());
    }

    public function testDeleteReturnsFalseWhenFoodDoesNotExist(): void
    {
        $result = $this->repository->delete(999);

        $this->assertFalse($result);
    }

    public function testGetCustomByUserIdReturnsOnlyUserFoods(): void
    {
        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs, created_by)
            VALUES ('Мой продукт 1', 100, 5, 2, 10, 1)
        ");

        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs, created_by)
            VALUES ('Мой продукт 2', 120, 6, 3, 12, 1)
        ");

        $this->pdo->exec("
            INSERT INTO foods (name, calories, proteins, fats, carbs, created_by)
            VALUES ('Чужой продукт', 150, 8, 4, 15, 2)
        ");

        $foods = $this->repository->getCustomByUserId(1);

        $this->assertCount(2, $foods);
        $this->assertEquals(1, $foods[0]->createdBy);
        $this->assertEquals(1, $foods[1]->createdBy);
    }

    public function testGetRecentByUserIdReturnsRecentlyUsedFoods(): void
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

        $foods = $this->repository->getRecentByUserId(1);

        $this->assertCount(2, $foods);
        $this->assertEquals('Гречка', $foods[0]->name);
        $this->assertEquals('Рис', $foods[1]->name);
    }

    public function testSaveThrowsExceptionForUnsupportedDto(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Неподдерживаемый тип DTO');

        $this->repository->save(new \stdClass());
    }
}
