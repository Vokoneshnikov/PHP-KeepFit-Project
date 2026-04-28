<?php

namespace App\Dtos\Requests;

use DateTimeImmutable;
use App\Enums\MealType;

class CreateMealRequest
{
    public function __construct(
        public int $userId,
        public int $foodId,
        public int $amountGrams,
        public MealType $mealType,
        public DateTimeImmutable $consumedAt
    ) {
    }
}
