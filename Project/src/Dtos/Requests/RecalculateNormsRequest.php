<?php

namespace App\Dtos\Requests;

use App\Enums\FitnessGoal;
class RecalculateNormsRequest
{
    public function __construct(
        public int $userId,
        public float $weight,
        public float $height,
        public float $activityLevel,
        public FitnessGoal $goal
    ) {}
}