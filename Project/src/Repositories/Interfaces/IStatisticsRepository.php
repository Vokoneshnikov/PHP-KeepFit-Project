<?php

namespace App\Repositories\Interfaces;

interface IStatisticsRepository
{
    function saveDailyNorm(int $userId, int $calories, float $proteins, float $fats, float $carbs): void;
    function saveUserParameters(int $userId, float $weight, int $height, string $activityLevel, string $goal): void;
    function getWeeklyProgress(int $userId): array;
    function getMonthlyProgress(int $userId): array;
    function getAveragesAndNorms(int $userId, string $period = 'week'): array;
}
