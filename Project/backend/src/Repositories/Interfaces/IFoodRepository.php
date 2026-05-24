<?php

namespace App\Repositories\Interfaces;

use App\Dtos\Responses\FoodResponse;

interface IFoodRepository extends IRepository
{
    public function search(string $query, int $userId): array;

    public function getAllVisibleForUser(int $userId): array;

    public function getVisibleById(int $id, int $userId): FoodResponse;

    public function getRecentByUserId(int $userId): array;
    public function getCustomByUserId(int $userId): array;
}