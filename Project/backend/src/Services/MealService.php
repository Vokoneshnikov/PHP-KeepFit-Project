<?php

namespace App\Services;

use App\Dtos\Requests\UpdateMealRequest;
use App\Enums\MealType;
use App\Repositories\Interfaces\IMealRepository;
use App\Repositories\Interfaces\IFoodRepository;
use App\Dtos\Requests\CreateMealRequest;
use App\Dtos\Responses\MealResponse;

class MealService
{
    public function __construct(
        private readonly IMealRepository $mealRepository,
        private readonly IFoodRepository $foodRepository
    ) {}

    public function addMealRecord(CreateMealRequest $request): MealResponse
    {
        if ($request->amountGrams <= 0) {
            throw new \InvalidArgumentException("Граммовка должна быть больше нуля");
        }
        return $this->mealRepository->save($request);
    }
    public function addFoodToLog(int $userId, int $foodId, float $weight, string $mealType): MealResponse
    {
        // Собираем DTO для репозитория
        $request = new CreateMealRequest(
            $userId,
        $foodId,
        $weight,
        MealType::from(strtolower($mealType)),
        new \DateTimeImmutable(),
        );

        return $this->addMealRecord($request);
    }
    public function getMealLogById(int $mealId): MealResponse
    {
        return $this->mealRepository->getById($mealId);
    }
    public function updateMealLogWeight(int $mealId, int $userId, float $newWeight): MealResponse
    {
        if ($newWeight <= 0) {
            throw new \InvalidArgumentException("Граммовка должна быть больше нуля");
        }

        $meal = $this->mealRepository->getById($mealId);

        if ($meal->userId !== $userId) {
            throw new \Exception("Доступ запрещен: это не ваша запись");
        }

        $updateRequest = new UpdateMealRequest(
            $mealId,
            $newWeight,
        );

        return $this->mealRepository->save($updateRequest);
    }

    public function deleteMealLogEntry(int $mealId, int $userId): bool
    {
        // 1. Получаем запись
        $meal = $this->mealRepository->getById($mealId);

        // 2. Проверяем владельца
        if ($meal->userId !== $userId) {
            throw new \Exception("Доступ запрещен: вы не можете удалить эту запись");
        }

        // 3. Удаляем
        return $this->mealRepository->delete($mealId);
    }
    public function calculateCalculatedCpfc(int $foodId, int $grams): array
    {
        $food = $this->foodRepository->getById($foodId);

        $proteins = round(($food->proteins * $grams) / 100, 1);
        $fats     = round(($food->fats * $grams) / 100, 1);
        $carbs    = round(($food->carbs * $grams) / 100, 1);
        $calories = round(($food->calories * $grams) / 100, 1);

        return [
            'calories' => $calories,
            'proteins' => $proteins,
            'fats' => $fats,
            'carbs' => $carbs,
        ];
    }
}
