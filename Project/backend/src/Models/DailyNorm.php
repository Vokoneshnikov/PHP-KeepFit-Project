<?php

namespace App\Models;

use DateTimeImmutable;

class DailyNorm
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly int $calories,
        public readonly float $proteins,
        public readonly float $fats,
        public readonly float $carbs,
        public readonly ?DateTimeImmutable $createdAt = null,
    ) {
    }
}
