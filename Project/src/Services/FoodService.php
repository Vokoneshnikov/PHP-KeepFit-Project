<?php

namespace App\Services;

use App\Repositories\Interfaces\IFoodRepository;
use App\Dtos\Requests\CreateFoodRequest;
use App\Dtos\Responses\FoodResponse;

class FoodService
{
    public function __construct(private readonly IFoodRepository $foodRepository) {}

    public function createFood(CreateFoodRequest $request): FoodResponse
    {
        if (empty($request->name)) {
            throw new \InvalidArgumentException("Название продукта не может быть пустым");
        }
        return $this->foodRepository->save($request);
    }

    public function getDefaultFoods(): array
    {
        return $this->foodRepository->getAll();
    }

    public function searchFoods(string $query): array
    {
        return $this->foodRepository->search($query);
    }

    public function getRecentFoods(int $userId): array
    {
        return $this->foodRepository->getRecentByUserId($userId);
    }

    public function getProductById(int $id): FoodResponse
    {
        return $this->foodRepository->getById($id);
    }
}