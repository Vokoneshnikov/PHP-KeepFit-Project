<?php

namespace App\Dtos\Requests;

class UpdateFoodRequest {
    public function __construct(
        public int $id,
        public string $name,
        public int $proteins,
        public int $fats,
        public int $carbs,
        public int $calories,
    ) {}
}
