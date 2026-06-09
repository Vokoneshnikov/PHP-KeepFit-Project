<?php

namespace App\Repositories\Interfaces;

interface IUserRepository extends IRepository
{
    public function findByEmail(string $email): ?\App\Dtos\Responses\UserResponse;
    public function getPasswordHashByEmail(string $email): ?string;
    public function getBirthDateById(int $id): string;
}
