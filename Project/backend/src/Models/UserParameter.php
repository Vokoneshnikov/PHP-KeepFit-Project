<?php

namespace App\Models;

use App\Enums\ActivityLevel;
use App\Enums\FitnessGoal;
use DateTimeImmutable;

class UserParameter
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly float $weight,
        public readonly int $height,
        public readonly ActivityLevel $activityLevel,
        public readonly FitnessGoal $goal,
        public readonly ?DateTimeImmutable $measuredAt = null,
    ) {
    }
}
