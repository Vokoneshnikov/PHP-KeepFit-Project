<?php

namespace Tests\Unit;

use App\Dtos\Requests\RecalculateNormsRequest;
use App\Dtos\Responses\UserResponse;
use App\Enums\FitnessGoal;
use App\Enums\Gender;
use App\Services\StatisticsService;
use App\Services\UserService;
use App\Repositories\Implementations\StatisticsRepository;
use PHPUnit\Framework\TestCase;

class BmrCalculationTest extends TestCase
{
    /**
     * Проверяет математику формулы Миффлина-Сан Жеора для мужчин
     */
    public function testMaleBmrAndTdeeCalculation()
    {
        // 1. Мокаем UserResponse (в котором нет birthDate)
        $userResponse = new UserResponse(
            id: 1,
            name: 'Александр',
            gender: Gender::Male,
            email: 'alex@fit.com'
        );

        $userServiceMock = $this->createMock(UserService::class);
        $userServiceMock->method('getProfile')->willReturn($userResponse);
        // А вот здесь мокаем наш новый метод! Возвращаем дату рождения (30 лет в 2026 году)
        $userServiceMock->method('getUserBirthDate')->willReturn('1996-05-19');

        // 2. Мокаем репозиторий статистики, проверяя, что вызовы записи происходят ровно 1 раз
        $statsRepoMock = $this->createMock(StatisticsRepository::class);
        $statsRepoMock->expects($this->once())->method('saveDailyNorm');
        $statsRepoMock->expects($this->once())->method('saveUserParameters');

        $service = new StatisticsService($userServiceMock, $statsRepoMock);

        // Вес 80кг, рост 180см, активность 1.55
        $request = new RecalculateNormsRequest(
            userId: 1,
            weight: 80.0,
            height: 180.0,
            activityLevel: 1.55,
            goal: FitnessGoal::Maintain
        );

        $response = $service->calculateAndSaveNorms($request);

        // BMR = (10 * 80) + (6.25 * 180) - (5 * 30) + 5 = 1780
        // TDEE = 1780 * 1.55 = 2759
        $this->assertEquals(2759, $response->dailyCalories);
        $this->assertEquals(206.9, $response->proteins);
        $this->assertEquals(92.0, $response->fats);
        $this->assertEquals(275.9, $response->carbs);
    }
}
