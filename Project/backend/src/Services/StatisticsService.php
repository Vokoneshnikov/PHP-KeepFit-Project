<?php

namespace App\Services;

use App\Dtos\Requests\RecalculateNormsRequest;
use App\Dtos\Responses\DailyNormsResponse;
use App\Enums\Gender;
use App\Enums\FitnessGoal;
use App\Repositories\Implementations\StatisticsRepository;

class StatisticsService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly StatisticsRepository $statisticsRepository
    ) {}

    public function calculateAndSaveNorms(RecalculateNormsRequest $request): DailyNormsResponse
    {
        $user = $this->userService->getProfile($request->userId);

        $birthDateString = $this->userService->getUserBirthDate($request->userId);
        $birthDate = new \DateTimeImmutable($birthDateString);

        $now = new \DateTimeImmutable();
        $age = $now->diff($birthDate)->y;

        // 3. Формула Миффлина-Сан Жеора (BMR)
        $bmr = (10 * $request->weight) + (6.25 * $request->height) - (5 * $age);

        if ($user->gender === Gender::Male) {
            $bmr += 5;
        } else {
            $bmr -= 161;
        }

        $tdee = $bmr * $request->activityLevel;

        $targetCalories = $this->applyGoalToCalories($tdee, $request->goal);

        // БЖУ: белки 30%, жиры 30%, углеводы 40%
        $proteins = ($targetCalories * 0.3) / 4;
        $fats = ($targetCalories * 0.3) / 9;
        $carbs = ($targetCalories * 0.4) / 4;

        $caloriesResult = (int)round($targetCalories);
        $proteinsResult = round($proteins, 1);
        $fatsResult = round($fats, 1);
        $carbsResult = round($carbs, 1);

        $this->statisticsRepository->saveDailyNorm(
            $request->userId,
            $caloriesResult,
            $proteinsResult,
            $fatsResult,
            $carbsResult
        );

        $this->statisticsRepository->saveUserParameters(
            $request->userId,
            $request->weight,
            (int)$request->height,
            $this->getActivityLevelName($request->activityLevel),
            $request->goal->value
        );

        return new DailyNormsResponse(
            userId: $request->userId,
            dailyCalories: $caloriesResult,
            proteins: $proteinsResult,
            fats: $fatsResult,
            carbs: $carbsResult
        );
    }

    private function applyGoalToCalories(float $tdee, FitnessGoal $goal): float
    {
        return match ($goal) {
            FitnessGoal::Lose => $tdee * 0.85,
            FitnessGoal::Maintain => $tdee,
            FitnessGoal::Gain => $tdee * 1.15,
        };
    }

    private function getActivityLevelName(float $activityLevel): string
    {
        return match (true) {
            abs($activityLevel - 1.2) < 0.001 => 'sedentary',
            abs($activityLevel - 1.375) < 0.001 => 'light',
            abs($activityLevel - 1.55) < 0.001 => 'moderate',
            abs($activityLevel - 1.725) < 0.001 => 'active',
            abs($activityLevel - 1.9) < 0.001 => 'very_active',
            default => 'moderate',
        };
    }

    public function getWeeklyData(int $userId): array
    {
        return [
            'summary' => $this->statisticsRepository->getAveragesAndNorms($userId, 'week'),
            'history' => $this->statisticsRepository->getWeeklyProgress($userId)
        ];
    }

    public function getMonthlyData(int $userId): array
    {
        return [
            'summary' => $this->statisticsRepository->getAveragesAndNorms($userId, 'month'),
            'history' => $this->statisticsRepository->getMonthlyProgress($userId)
        ];
    }
    public function getProfileStatistics(int $userId): array
    {
        $latestParameters = $this->statisticsRepository->getLatestUserParameters($userId);
        $latestDailyNorm = $this->statisticsRepository->getLatestDailyNorm($userId);

        $monthlyStats = $this->statisticsRepository->getAveragesAndNorms($userId, 'month');

        return [
            'parameters' => $latestParameters ?? [
                    'weight' => null,
                    'height' => null,
                    'activityLevel' => null,
                    'goal' => null,
                    'measuredAt' => null,
                ],
            'dailyNorm' => $latestDailyNorm ?? [
                    'calories' => 0,
                    'proteins' => 0.0,
                    'fats' => 0.0,
                    'carbs' => 0.0,
                    'createdAt' => null,
                ],
            'monthlyAverage' => $monthlyStats['avg'] ?? [
                    'calories' => 0,
                    'proteins' => 0.0,
                    'fats' => 0.0,
                    'carbs' => 0.0,
                ],
        ];
    }
}