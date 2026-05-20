<?php

namespace App\Dtos\Requests;

class CreateFoodRequest
{
    public function __construct(
        public string $name,
        public float $calories,
        public float $proteins,
        public float $fats,
        public float $carbs,
        public ?int $createdBy = null,
    ) {
    }
}
