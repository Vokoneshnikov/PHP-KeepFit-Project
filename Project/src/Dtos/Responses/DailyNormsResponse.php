<?php

namespace App\Dtos\Responses;

class DailyNormsResponse
{
    public function __construct(
        public int $userId,
        public int $dailyCalories,
        public float $proteins,
        public float $fats,
        public float $carbs
    ) {}
}
