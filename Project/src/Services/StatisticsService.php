<?php

namespace App\Services;

use App\Dtos\Requests\RecalculateNormsRequest;
use App\Dtos\Responses\DailyNormsResponse;
use App\Enums\Gender;
use App\Repositories\Implementations\StatisticsRepository;

class StatisticsService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly StatisticsRepository $statisticsRepository
    ) {}

    public function calculateAndSaveNorms(RecalculateNormsRequest $request): DailyNormsResponse
    {
        // 1. Получаем профиль для доступа к гендеру
        $user = $this->userService->getProfile($request->userId);

        // 2. Получаем дату рождения (извлекаем через твой UserRepository или UserService)
        $birthDateString = $this->userService->getUserBirthDate($request->userId);
        $birthDate = new \DateTimeImmutable($birthDateString);

        $now = new \DateTimeImmutable();
        $age = $now->diff($birthDate)->y;

        // 3. Формула Миффлина-Сан Жеора (BMR)
        $bmr = (10 * $request->weight) + (6.25 * $request->height) - (5 * $age);
        $bmr += ($user->gender === Gender::Male) ? 5 : -161;

        // 4. Переводим activityLevel (который у тебя может быть числом или Enum) в коэффициент
        // Если из контроллера уже приходит float, используем его напрямую:
        $factor = $request->activityLevel;

        $tdee = $bmr * $factor;

        // 5. Расчет БЖУ (30% / 30% / 40%)
        $proteins = ($tdee * 0.3) / 4;
        $fats = ($tdee * 0.3) / 9;
        $carbs = ($tdee * 0.4) / 4;

        $caloriesResult = (int)round($tdee);
        $proteinsResult = round($proteins, 1);
        $fatsResult     = round($fats, 1);
        $carbsResult    = round($carbs, 1);

        // Сохраняем норму КБЖУ в daily_norms
        $this->statisticsRepository->saveDailyNorm(
            $request->userId,
            $caloriesResult,
            $proteinsResult,
            $fatsResult,
            $carbsResult
        );

        // Синхронно сохраняем физические параметры в user_parameters
        // (Предполагаем, что у тебя есть под это методы в репозитории)
        $this->statisticsRepository->saveUserParameters(
            $request->userId,
            $request->weight,
            $request->height,
            'active', // Здесь должен быть твой реальный эквивалент Enum activity_level
            'maintain' // Твой реальный эквивалент Enum fitness_goal
        );

        return new DailyNormsResponse(
            userId: $request->userId,
            dailyCalories: $caloriesResult,
            proteins: $proteinsResult,
            fats: $fatsResult,
            carbs: $carbsResult
        );
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
}
