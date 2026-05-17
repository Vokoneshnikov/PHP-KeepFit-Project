<?php

namespace Tests\Unit;

use App\Dtos\Responses\MealResponse;
use App\Enums\MealType;
use PHPUnit\Framework\TestCase;
use App\Services\MealService;
use App\Dtos\Requests\CreateMealRequest;
use App\Repositories\Interfaces\IMealRepository;
use App\Repositories\Interfaces\IFoodRepository;
use App\Dtos\Responses\FoodResponse;

class MealServiceTest extends TestCase
{

    //Тест 1-3: Проверка корректности пересчета КБЖУ с использованием DataProvider
    public function testCalculateCalculatedCpfc(
        float $productCalories, float $productProteins, float $productFats, float $productCarbs,
        int $grams,
        float $expectedCalories, float $expectedProteins, float $expectedFat, float $expectedCarbs
    ) {

        $foodRepositoryMock = $this->createMock(IFoodRepository::class);
        $mealRepositoryMock = $this->createMock(IMealRepository::class);

        // Настраиваем фейковый ответ репозитория, как будто продукт найден в БД
        $fakeProduct = new FoodResponse(
            id: 1,
            name: 'Тестовый продукт',
            calories: $productCalories,
            proteins: $productProteins,
            fats: $productFats,
            carbs: $productCarbs
        );

        $foodRepositoryMock->method('getById')->willReturn($fakeProduct);

        $service = new MealService($mealRepositoryMock, $foodRepositoryMock);
        $result = $service->calculateCalculatedCpfc(1, $grams);

        // Проверяем математику и округление до 1 знака
        $this->assertEquals($expectedCalories, $result['calories']);
        $this->assertEquals($expectedProteins, $result['proteins']);
        $this->assertEquals($expectedFat, $result['fats']);
        $this->assertEquals($expectedCarbs, $result['carbs']);
    }

    public static function CpfcDataProvider(): array
    {
        return [
            'Куриная грудка 150г (округление вверх)' => [
                113.0, 23.6, 1.9, 0.4,
                150, // Вес порции
                169.5, 35.4, 2.9, 0.6
            ],
            'Гречка отварная 200г' => [
                110.0, 4.2, 1.1, 21.3,
                200,
                220.0, 8.4, 2.2, 42.6
            ],
            'Сложное округление до 1 знака (11.48 -> 11.5)' => [
                100.0, 7.65, 0.0, 0.0,
                150,
                150.0, 11.5, 0.0, 0.0
            ]
        ];
    }

    //Тест 4: Проверка исключения при невалидной граммовке порции
    public function testAddMealRecordThrowsExceptionForZeroGrams()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Граммовка должна быть больше нуля");

        $foodRepositoryMock = $this->createMock(IFoodRepository::class);
        $mealRepositoryMock = $this->createMock(IMealRepository::class);

        $service = new MealService($mealRepositoryMock, $foodRepositoryMock);

        $badRequest = new CreateMealRequest(
            userId: 1,
            foodId: 1,
            amountGrams: 0,
            mealType: MealType::Breakfast,
            consumedAt: new \DateTimeImmutable()
        );

        $service->addMealRecord($badRequest);
    }

    //Тест 5: Успешное добавление записи приема пищи
    public function testAddMealRecordSuccess()
    {
        $foodRepositoryMock = $this->createMock(IFoodRepository::class);
        $mealRepositoryMock = $this->createMock(IMealRepository::class);

        $request = new CreateMealRequest(
            userId: 1,
            foodId: 5,
            amountGrams: 250,
            mealType: MealType::Lunch,
            consumedAt: new \DateTimeImmutable('2026-05-17')
        );

        $expectedResponse = new MealResponse(
            id: 99,
            userId: 1,
            foodId: 5,
            amountGrams: 250,
            mealType: MealType::Lunch,
            consumedAt: new \DateTimeImmutable('2026-05-17')
        );

        $mealRepositoryMock->expects($this->once())
            ->method('save')
            ->willReturn($expectedResponse);

        $service = new MealService($mealRepositoryMock, $foodRepositoryMock);
        $result = $service->addMealRecord($request);

        $this->assertEquals(99, $result->id);
        $this->assertEquals(250, $result->amountGrams);
    }
}
