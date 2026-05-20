<?php

namespace App\Dtos\Responses;

class FoodResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public float $calories,
        public float $proteins,
        public float $fats,
        public float $carbs,
        public ?int $createdBy = null,
    ) {
    }
}
