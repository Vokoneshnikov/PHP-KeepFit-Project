<?php

namespace App\Dtos\Requests;

use DateTimeImmutable;
use App\Enums\Gender;

class CreateUserRequest
{
    public function __construct(
        public string $password,
        public string $email,
        public string $name,
        public Gender $gender,
        public DateTimeImmutable $birthDate,
    ) {
    }
}
