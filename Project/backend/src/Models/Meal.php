<?php

namespace App\Models;

use App\Enums\MealType;
use DateTimeImmutable;

class Meal
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly int $foodId,
        public readonly float $amountGrams,
        public readonly MealType $mealType,
        public readonly DateTimeImmutable $consumedAt,
    ) {
    }
}
