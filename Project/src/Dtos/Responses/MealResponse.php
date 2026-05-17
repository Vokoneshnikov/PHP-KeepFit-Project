<?php

namespace App\Dtos\Responses;

use DateTimeImmutable;
use App\Enums\MealType;

class MealResponse
{
    public function __construct(
        public int $id,
        public int $userId,
        public int $foodId,
        public float $amountGrams,
        public MealType $mealType,
        public DateTimeImmutable $consumedAt
    ) {
    }
}
