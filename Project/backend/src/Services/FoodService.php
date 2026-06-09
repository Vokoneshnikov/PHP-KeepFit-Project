<?php

namespace App\Services;

use App\Repositories\Interfaces\IFoodRepository;
use App\Dtos\Requests\CreateFoodRequest;
use App\Dtos\Requests\UpdateFoodRequest;
use App\Dtos\Responses\FoodResponse;

class FoodService
{
    public function __construct(private readonly IFoodRepository $foodRepository)
    {
    }

    public function createFood(CreateFoodRequest $request): FoodResponse
    {
        if (empty($request->name)) {
            throw new \InvalidArgumentException("Название продукта не может быть пустым");
        }

        if ($request->calories < 0 || $request->proteins < 0 || $request->fats < 0 || $request->carbs < 0) {
            throw new \InvalidArgumentException("КБЖУ не может быть отрицательным");
        }

        return $this->foodRepository->save($request);
    }

    public function getDefaultFoods(int $userId): array
    {
        return $this->foodRepository->getAllVisibleForUser($userId);
    }

    public function searchFoods(string $query, int $userId): array
    {
        return $this->foodRepository->search($query, $userId);
    }

    public function getVisibleProductById(int $id, int $userId): FoodResponse
    {
        return $this->foodRepository->getVisibleById($id, $userId);
    }

    public function getProductById(int $id): FoodResponse
    {
        return $this->foodRepository->getById($id);
    }

    public function getRecentFoods(int $userId): array
    {
        return $this->foodRepository->getRecentByUserId($userId);
    }

    public function getCustomFoods(int $userId): array
    {
        return $this->foodRepository->getCustomByUserId($userId);
    }

    public function getCustomFoodById(int $foodId, int $userId): FoodResponse
    {
        $food = $this->foodRepository->getById($foodId);

        if ($food->createdBy !== $userId) {
            throw new \Exception("Доступ запрещен: это не ваш продукт");
        }

        return $food;
    }

    public function updateCustomFood(UpdateFoodRequest $request, int $userId): FoodResponse
    {
        $food = $this->foodRepository->getById($request->id);

        if ($food->createdBy !== $userId) {
            throw new \Exception("Доступ запрещен: вы не можете редактировать этот продукт");
        }

        if (empty($request->name)) {
            throw new \InvalidArgumentException("Название продукта не может быть пустым");
        }

        if ($request->calories < 0 || $request->proteins < 0 || $request->fats < 0 || $request->carbs < 0) {
            throw new \InvalidArgumentException("КБЖУ не может быть отрицательным");
        }

        return $this->foodRepository->save($request);
    }

    public function deleteCustomFood(int $foodId, int $userId): bool
    {
        $food = $this->foodRepository->getById($foodId);

        if ($food->createdBy !== $userId) {
            throw new \Exception("Доступ запрещен: вы не можете удалить этот продукт");
        }

        return $this->foodRepository->delete($foodId);
    }
}
