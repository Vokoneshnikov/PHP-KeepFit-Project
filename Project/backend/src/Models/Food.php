<?php

namespace App\Models;

class Food
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly float $calories,
        public readonly float $proteins,
        public readonly float $fats,
        public readonly float $carbs,
        public readonly ?int $createdBy = null,
    ) {
    }
}
