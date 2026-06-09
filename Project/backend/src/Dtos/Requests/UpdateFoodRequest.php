<?php

namespace App\Dtos\Requests;

class UpdateFoodRequest
{
    public function __construct(
        public int $id,
        public string $name,
        public float $proteins,
        public float $fats,
        public float $carbs,
        public float $calories,
    ) {
    }
}
