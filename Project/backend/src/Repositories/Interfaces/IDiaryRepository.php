<?php

namespace App\Repositories\Interfaces;

interface IDiaryRepository
{
    public function getLatestDailyNorm(int $userId): array;

    public function getMealItemsForDate(int $userId, string $date): array;
}
