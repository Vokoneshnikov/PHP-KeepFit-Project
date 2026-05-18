<?php

namespace App\Dtos\Requests;

class RecalculateNormsRequest
{
    public function __construct(
        public int $userId,
        public float $weight,
        public float $height,
        public float $activityLevel
    ) {}
}
