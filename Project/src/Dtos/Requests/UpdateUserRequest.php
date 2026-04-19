<?php
namespace App\Dtos\Requests;

use DateTimeImmutable;
use App\Enums\Gender;

class UpdateUserRequest {
    public function __construct(
        public int $id,
        public string $email,
        public ?string $name = null,
        public ?Gender $gender = null,
        public ?DateTimeImmutable $birthDate = null,
    ) {}

}
