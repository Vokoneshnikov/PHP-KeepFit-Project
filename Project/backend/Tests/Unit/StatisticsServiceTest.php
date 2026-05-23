<?php

namespace backend\Tests\Unit;

use App\Dtos\Requests\RecalculateNormsRequest;
use App\Dtos\Responses\UserResponse;
use App\Enums\FitnessGoal;
use App\Enums\Gender;
use App\Repositories\Implementations\StatisticsRepository;
use App\Services\StatisticsService;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;

class StatisticsServiceTest extends TestCase
{
    public function testCalculateAndSaveNormsForMaleMaintainGoal(): void
    {
        $userId = 1;
        $birthDate = '1996-01-01';
        $weight = 80.0;
        $height = 180.0;
        $activityLevel = 1.55;
        $goal = FitnessGoal::Maintain;

        $expected = $this->calculateExpectedNorms(
            gender: Gender::Male,
            birthDate: $birthDate,
            weight: $weight,
            height: $height,
            activityLevel: $activityLevel,
            goal: $goal
        );

        $statisticsRepositoryMock = $this->createMock(StatisticsRepository::class);

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('saveDailyNorm')
            ->with(
                $userId,
                $expected['calories'],
                $expected['proteins'],
                $expected['fats'],
                $expected['carbs']
            );

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('saveUserParameters')
            ->with(
                $userId,
                $weight,
                (int)$height,
                'moderate',
                'maintain'
            );

        $service = $this->createService(
            gender: Gender::Male,
            birthDate: $birthDate,
            statisticsRepositoryMock: $statisticsRepositoryMock
        );

        $request = new RecalculateNormsRequest(
            userId: $userId,
            weight: $weight,
            height: $height,
            activityLevel: $activityLevel,
            goal: $goal
        );

        $result = $service->calculateAndSaveNorms($request);

        $this->assertEquals($userId, $result->userId);
        $this->assertEquals($expected['calories'], $result->dailyCalories);
        $this->assertEquals($expected['proteins'], $result->proteins);
        $this->assertEquals($expected['fats'], $result->fats);
        $this->assertEquals($expected['carbs'], $result->carbs);
    }

    public function testCalculateAndSaveNormsForFemaleLoseGoal(): void
    {
        $userId = 1;
        $birthDate = '1996-01-01';
        $weight = 70.0;
        $height = 165.0;
        $activityLevel = 1.2;
        $goal = FitnessGoal::Lose;

        $expected = $this->calculateExpectedNorms(
            gender: Gender::Female,
            birthDate: $birthDate,
            weight: $weight,
            height: $height,
            activityLevel: $activityLevel,
            goal: $goal
        );

        $statisticsRepositoryMock = $this->createMock(StatisticsRepository::class);

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('saveDailyNorm')
            ->with(
                $userId,
                $expected['calories'],
                $expected['proteins'],
                $expected['fats'],
                $expected['carbs']
            );

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('saveUserParameters')
            ->with(
                $userId,
                $weight,
                (int)$height,
                'sedentary',
                'lose'
            );

        $service = $this->createService(
            gender: Gender::Female,
            birthDate: $birthDate,
            statisticsRepositoryMock: $statisticsRepositoryMock
        );

        $request = new RecalculateNormsRequest(
            userId: $userId,
            weight: $weight,
            height: $height,
            activityLevel: $activityLevel,
            goal: $goal
        );

        $result = $service->calculateAndSaveNorms($request);

        $this->assertEquals($expected['calories'], $result->dailyCalories);
        $this->assertEquals($expected['proteins'], $result->proteins);
        $this->assertEquals($expected['fats'], $result->fats);
        $this->assertEquals($expected['carbs'], $result->carbs);
    }

    public function testCalculateAndSaveNormsForMaleGainGoal(): void
    {
        $userId = 1;
        $birthDate = '1996-01-01';
        $weight = 90.0;
        $height = 185.0;
        $activityLevel = 1.9;
        $goal = FitnessGoal::Gain;

        $expected = $this->calculateExpectedNorms(
            gender: Gender::Male,
            birthDate: $birthDate,
            weight: $weight,
            height: $height,
            activityLevel: $activityLevel,
            goal: $goal
        );

        $statisticsRepositoryMock = $this->createMock(StatisticsRepository::class);

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('saveDailyNorm')
            ->with(
                $userId,
                $expected['calories'],
                $expected['proteins'],
                $expected['fats'],
                $expected['carbs']
            );

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('saveUserParameters')
            ->with(
                $userId,
                $weight,
                (int)$height,
                'very_active',
                'gain'
            );

        $service = $this->createService(
            gender: Gender::Male,
            birthDate: $birthDate,
            statisticsRepositoryMock: $statisticsRepositoryMock
        );

        $request = new RecalculateNormsRequest(
            userId: $userId,
            weight: $weight,
            height: $height,
            activityLevel: $activityLevel,
            goal: $goal
        );

        $result = $service->calculateAndSaveNorms($request);

        $this->assertEquals($expected['calories'], $result->dailyCalories);
        $this->assertEquals($expected['proteins'], $result->proteins);
        $this->assertEquals($expected['fats'], $result->fats);
        $this->assertEquals($expected['carbs'], $result->carbs);
    }

    public function testCalculateAndSaveNormsUsesDefaultActivityLevelName(): void
    {
        $userId = 1;
        $birthDate = '1996-01-01';
        $weight = 80.0;
        $height = 180.0;
        $activityLevel = 1.333;
        $goal = FitnessGoal::Maintain;

        $expected = $this->calculateExpectedNorms(
            gender: Gender::Male,
            birthDate: $birthDate,
            weight: $weight,
            height: $height,
            activityLevel: $activityLevel,
            goal: $goal
        );

        $statisticsRepositoryMock = $this->createMock(StatisticsRepository::class);

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('saveDailyNorm')
            ->with(
                $userId,
                $expected['calories'],
                $expected['proteins'],
                $expected['fats'],
                $expected['carbs']
            );

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('saveUserParameters')
            ->with(
                $userId,
                $weight,
                (int)$height,
                'moderate',
                'maintain'
            );

        $service = $this->createService(
            gender: Gender::Male,
            birthDate: $birthDate,
            statisticsRepositoryMock: $statisticsRepositoryMock
        );

        $request = new RecalculateNormsRequest(
            userId: $userId,
            weight: $weight,
            height: $height,
            activityLevel: $activityLevel,
            goal: $goal
        );

        $result = $service->calculateAndSaveNorms($request);

        $this->assertEquals($expected['calories'], $result->dailyCalories);
    }

    public function testGetWeeklyDataReturnsSummaryAndHistory(): void
    {
        $statisticsRepositoryMock = $this->createMock(StatisticsRepository::class);

        $summary = [
            'avg' => ['calories' => 2000],
            'target' => ['calories' => 2300],
        ];

        $history = [
            ['date' => '2026-05-18', 'consumed_calories' => 1900],
            ['date' => '2026-05-19', 'consumed_calories' => 2100],
        ];

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('getAveragesAndNorms')
            ->with(1, 'week')
            ->willReturn($summary);

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('getWeeklyProgress')
            ->with(1)
            ->willReturn($history);

        $service = $this->createService(
            gender: Gender::Male,
            birthDate: '1996-01-01',
            statisticsRepositoryMock: $statisticsRepositoryMock
        );

        $result = $service->getWeeklyData(1);

        $this->assertEquals($summary, $result['summary']);
        $this->assertEquals($history, $result['history']);
    }

    public function testGetMonthlyDataReturnsSummaryAndHistory(): void
    {
        $statisticsRepositoryMock = $this->createMock(StatisticsRepository::class);

        $summary = [
            'avg' => ['calories' => 2100],
            'target' => ['calories' => 2400],
        ];

        $history = [
            ['date' => '2026-05-01', 'consumed_calories' => 2000],
            ['date' => '2026-05-02', 'consumed_calories' => 2200],
        ];

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('getAveragesAndNorms')
            ->with(1, 'month')
            ->willReturn($summary);

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('getMonthlyProgress')
            ->with(1)
            ->willReturn($history);

        $service = $this->createService(
            gender: Gender::Male,
            birthDate: '1996-01-01',
            statisticsRepositoryMock: $statisticsRepositoryMock
        );

        $result = $service->getMonthlyData(1);

        $this->assertEquals($summary, $result['summary']);
        $this->assertEquals($history, $result['history']);
    }

    public function testGetProfileStatisticsReturnsActualData(): void
    {
        $statisticsRepositoryMock = $this->createMock(StatisticsRepository::class);

        $parameters = [
            'weight' => 80.0,
            'height' => 180,
            'activityLevel' => 'moderate',
            'goal' => 'maintain',
            'measuredAt' => '2026-05-21 10:00:00',
        ];

        $dailyNorm = [
            'calories' => 2300,
            'proteins' => 160.0,
            'fats' => 75.0,
            'carbs' => 260.0,
            'createdAt' => '2026-05-21 10:00:00',
        ];

        $monthlyStats = [
            'avg' => [
                'calories' => 2000,
                'proteins' => 140.0,
                'fats' => 65.0,
                'carbs' => 230.0,
            ],
        ];

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('getLatestUserParameters')
            ->with(1)
            ->willReturn($parameters);

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('getLatestDailyNorm')
            ->with(1)
            ->willReturn($dailyNorm);

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('getAveragesAndNorms')
            ->with(1, 'month')
            ->willReturn($monthlyStats);

        $service = $this->createService(
            gender: Gender::Male,
            birthDate: '1996-01-01',
            statisticsRepositoryMock: $statisticsRepositoryMock
        );

        $result = $service->getProfileStatistics(1);

        $this->assertEquals($parameters, $result['parameters']);
        $this->assertEquals($dailyNorm, $result['dailyNorm']);
        $this->assertEquals($monthlyStats['avg'], $result['monthlyAverage']);
    }

    public function testGetProfileStatisticsReturnsDefaultDataWhenRepositoryHasNoData(): void
    {
        $statisticsRepositoryMock = $this->createMock(StatisticsRepository::class);

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('getLatestUserParameters')
            ->with(1)
            ->willReturn(null);

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('getLatestDailyNorm')
            ->with(1)
            ->willReturn(null);

        $statisticsRepositoryMock
            ->expects($this->once())
            ->method('getAveragesAndNorms')
            ->with(1, 'month')
            ->willReturn([]);

        $service = $this->createService(
            gender: Gender::Male,
            birthDate: '1996-01-01',
            statisticsRepositoryMock: $statisticsRepositoryMock
        );

        $result = $service->getProfileStatistics(1);

        $this->assertEquals([
            'weight' => null,
            'height' => null,
            'activityLevel' => null,
            'goal' => null,
            'measuredAt' => null,
        ], $result['parameters']);

        $this->assertEquals([
            'calories' => 0,
            'proteins' => 0.0,
            'fats' => 0.0,
            'carbs' => 0.0,
            'createdAt' => null,
        ], $result['dailyNorm']);

        $this->assertEquals([
            'calories' => 0,
            'proteins' => 0.0,
            'fats' => 0.0,
            'carbs' => 0.0,
        ], $result['monthlyAverage']);
    }

    private function createService(
        Gender $gender,
        string $birthDate,
        StatisticsRepository $statisticsRepositoryMock
    ): StatisticsService {
        $userServiceMock = $this->createMock(UserService::class);

        $userServiceMock
            ->method('getProfile')
            ->willReturn(new UserResponse(
                id: 1,
                name: 'Тестовый пользователь',
                gender: $gender,
                email: 'test@fit.com'
            ));

        $userServiceMock
            ->method('getUserBirthDate')
            ->willReturn($birthDate);

        return new StatisticsService(
            $userServiceMock,
            $statisticsRepositoryMock
        );
    }

    private function calculateExpectedNorms(
        Gender $gender,
        string $birthDate,
        float $weight,
        float $height,
        float $activityLevel,
        FitnessGoal $goal
    ): array {
        $birthDateObject = new \DateTimeImmutable($birthDate);
        $now = new \DateTimeImmutable();
        $age = $now->diff($birthDateObject)->y;

        $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age);

        if ($gender === Gender::Male) {
            $bmr += 5;
        } else {
            $bmr -= 161;
        }

        $tdee = $bmr * $activityLevel;

        $targetCalories = match ($goal) {
            FitnessGoal::Lose => $tdee * 0.85,
            FitnessGoal::Maintain => $tdee,
            FitnessGoal::Gain => $tdee * 1.15,
        };

        return [
            'calories' => (int)round($targetCalories),
            'proteins' => round(($targetCalories * 0.3) / 4, 1),
            'fats' => round(($targetCalories * 0.3) / 9, 1),
            'carbs' => round(($targetCalories * 0.4) / 4, 1),
        ];
    }
}
