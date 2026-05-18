<?php

namespace App\Services;

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

    /**
     * Математика пересчета КБЖУ продукта под конкретный вес порции
     */
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
