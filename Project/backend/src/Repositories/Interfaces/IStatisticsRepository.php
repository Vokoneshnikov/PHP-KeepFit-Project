<?php

namespace App\Repositories\Interfaces;

interface IStatisticsRepository
{
    public function saveDailyNorm(int $userId, int $calories, float $proteins, float $fats, float $carbs): void;
    public function saveUserParameters(int $userId, float $weight, int $height, string $activityLevel, string $goal): void;
    public function getWeeklyProgress(int $userId): array;
    public function getMonthlyProgress(int $userId): array;
    public function getAveragesAndNorms(int $userId, string $period = 'week'): array;
    public function getLatestUserParameters(int $userId): ?array;
    public function getLatestDailyNorm(int $userId): ?array;
}