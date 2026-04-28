<?php

namespace App\Dtos\Responses;

class FoodResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $calories,
        public int $proteins,
        public int $fats,
        public int $carbs,
        public ?int $createdBy = null,
    ) {
    }
}
