<?php

namespace App\Models;

use App\Enums\Gender;
use DateTimeImmutable;

class User
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly string $name,
        public readonly Gender $gender,
        public readonly DateTimeImmutable $birthDate,
        public readonly ?DateTimeImmutable $createdAt = null,
    ) {
    }
}