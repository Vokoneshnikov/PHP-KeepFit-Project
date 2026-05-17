<?php

namespace App\Services;

use App\Repositories\Interfaces\IFoodRepository;
use App\Dtos\Requests\CreateFoodRequest;
use App\Dtos\Responses\FoodResponse;

class FoodService
{
    public function __construct(private IFoodRepository $foodRepository) {}

    public function createFood(CreateFoodRequest $request): FoodResponse
    {
        if (empty($request->name)) {
            throw new \InvalidArgumentException("Название продукта не может быть пустым");
        }
        return $this->foodRepository->save($request);
    }
}