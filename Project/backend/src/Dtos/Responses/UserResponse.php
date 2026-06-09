<?php

namespace App\Dtos\Responses;

use App\Enums\Gender;

class UserResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public Gender $gender,
        public string $email,
    ) {
    }
}
