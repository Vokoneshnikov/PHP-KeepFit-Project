<?php

namespace App\Repositories\Interfaces;

interface IFoodRepository extends IRepository
{
    public function search(string $query): array;
    public function getRecentByUserId(int $userId): array;
}
