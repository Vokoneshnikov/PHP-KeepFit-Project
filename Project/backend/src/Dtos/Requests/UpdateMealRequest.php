<?php

namespace App\Dtos\Requests;

use DateTimeImmutable;
use App\Enums\MealType;

class UpdateMealRequest
{
    public function __construct(
        public int $id,
        public ?float $amountGrams = null,
        public ?MealType $mealType = null,
    ) {
    }
}
