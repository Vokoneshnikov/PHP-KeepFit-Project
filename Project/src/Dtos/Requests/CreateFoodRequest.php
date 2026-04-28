<?php

namespace App\Dtos\Requests;

class CreateFoodRequest
{
    public function __construct(
        public string $name,
        public int $calories,
        public int $proteins,
        public int $fats,
        public int $carbs,
        public ?int $createdBy = null,
    ) {
    }
}
