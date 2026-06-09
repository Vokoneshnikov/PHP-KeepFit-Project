<?php

namespace backend\Tests\Unit;

use App\Repositories\Interfaces\IDiaryRepository;
use App\Services\DiaryService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DiaryServiceTest extends TestCase
{
    public function testGetDiaryForDateReturnsGroupedMealsAndTotals(): void
    {
        $repositoryMock = $this->createMock(IDiaryRepository::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $repositoryMock
            ->expects($this->once())
            ->method('getLatestDailyNorm')
            ->with(1)
            ->willReturn([
                'calories' => 2000,
                'proteins' => 150.0,
                'fats' => 70.0,
                'carbs' => 250.0,
            ]);

        $repositoryMock
            ->expects($this->once())
            ->method('getMealItemsForDate')
            ->with(1, '2026-05-21')
            ->willReturn([
                [
                    'meal_id' => 10,
                    'food_id' => 5,
                    'amount_grams' => 200,
                    'meal_type' => 'breakfast',
                    'food_name' => 'Овсянка',
                    'calories' => 68,
                    'proteins' => 2.5,
                    'fats' => 1.5,
                    'carbs' => 12,
                ],
                [
                    'meal_id' => 11,
                    'food_id' => 6,
                    'amount_grams' => 150,
                    'meal_type' => 'lunch',
                    'food_name' => 'Куриная грудка',
                    'calories' => 113,
                    'proteins' => 23.6,
                    'fats' => 1.9,
                    'carbs' => 0.4,
                ],
            ]);

        $service = new DiaryService($repositoryMock, $loggerMock);

        $result = $service->getDiaryForDate(1, '2026-05-21');

        $this->assertEquals('2026-05-21', $result['date']);

        $this->assertEquals(305.5, $result['totals']['calories']);
        $this->assertEquals(40.4, $result['totals']['proteins']);
        $this->assertEquals(5.9, $result['totals']['fats']);
        $this->assertEquals(24.6, $result['totals']['carbs']);

        $this->assertEquals(305.5, $result['caloriesRatio']['consumed']);
        $this->assertEquals(2000, $result['caloriesRatio']['target']);
        $this->assertEquals(1694.5, $result['caloriesRatio']['remaining']);
        $this->assertEquals(15.3, $result['caloriesRatio']['percent']);

        $this->assertCount(1, $result['meals']['breakfast']);
        $this->assertCount(1, $result['meals']['lunch']);
        $this->assertCount(0, $result['meals']['dinner']);
        $this->assertCount(0, $result['meals']['other']);

        $this->assertEquals('Овсянка', $result['meals']['breakfast'][0]['name']);
        $this->assertEquals(136.0, $result['meals']['breakfast'][0]['cpfc']['calories']);

        $this->assertEquals('Куриная грудка', $result['meals']['lunch'][0]['name']);
        $this->assertEquals(169.5, $result['meals']['lunch'][0]['cpfc']['calories']);
    }

    public function testGetDiaryForDateUsesOtherWhenMealTypeIsUnknown(): void
    {
        $repositoryMock = $this->createMock(IDiaryRepository::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $repositoryMock
            ->method('getLatestDailyNorm')
            ->willReturn([
                'calories' => 1000,
                'proteins' => 100.0,
                'fats' => 50.0,
                'carbs' => 150.0,
            ]);

        $repositoryMock
            ->method('getMealItemsForDate')
            ->willReturn([
                [
                    'meal_id' => 1,
                    'food_id' => 1,
                    'amount_grams' => 100,
                    'meal_type' => 'unknown',
                    'food_name' => 'Тестовый продукт',
                    'calories' => 100,
                    'proteins' => 10,
                    'fats' => 5,
                    'carbs' => 20,
                ],
            ]);

        $service = new DiaryService($repositoryMock, $loggerMock);

        $result = $service->getDiaryForDate(1, '2026-05-21');

        $this->assertCount(1, $result['meals']['other']);
        $this->assertEquals('Тестовый продукт', $result['meals']['other'][0]['name']);
    }

    public function testGetDiaryForDateReturnsZeroPercentWhenDailyNormIsZero(): void
    {
        $repositoryMock = $this->createMock(IDiaryRepository::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $repositoryMock
            ->method('getLatestDailyNorm')
            ->willReturn([
                'calories' => 0,
                'proteins' => 0.0,
                'fats' => 0.0,
                'carbs' => 0.0,
            ]);

        $repositoryMock
            ->method('getMealItemsForDate')
            ->willReturn([
                [
                    'meal_id' => 1,
                    'food_id' => 1,
                    'amount_grams' => 100,
                    'meal_type' => 'breakfast',
                    'food_name' => 'Яблоко',
                    'calories' => 52,
                    'proteins' => 0.3,
                    'fats' => 0.2,
                    'carbs' => 14,
                ],
            ]);

        $service = new DiaryService($repositoryMock, $loggerMock);

        $result = $service->getDiaryForDate(1, '2026-05-21');

        $this->assertEquals(52.0, $result['caloriesRatio']['consumed']);
        $this->assertEquals(0, $result['caloriesRatio']['target']);
        $this->assertEquals(0, $result['caloriesRatio']['remaining']);
        $this->assertEquals(0, $result['caloriesRatio']['percent']);
    }

    public function testGetDiaryForDateThrowsExceptionWhenDateIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Некорректная дата. Используйте формат YYYY-MM-DD');

        $repositoryMock = $this->createMock(IDiaryRepository::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $repositoryMock
            ->expects($this->never())
            ->method('getLatestDailyNorm');

        $repositoryMock
            ->expects($this->never())
            ->method('getMealItemsForDate');

        $service = new DiaryService($repositoryMock, $loggerMock);

        $service->getDiaryForDate(1, '21.05.2026');
    }

    public function testGetDiaryForDateLogsAndThrowsExceptionWhenRepositoryFails(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Не удалось получить дневник питания');

        $repositoryMock = $this->createMock(IDiaryRepository::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $repositoryMock
            ->method('getLatestDailyNorm')
            ->willThrowException(new \Exception('Ошибка БД'));

        $loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                'Ошибка DiaryService::getDiaryForDate',
                $this->arrayHasKey('exception')
            );

        $service = new DiaryService($repositoryMock, $loggerMock);

        $service->getDiaryForDate(1, '2026-05-21');
    }
}