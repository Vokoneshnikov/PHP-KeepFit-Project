<?php

namespace App\Controllers;

use App\Core\Route;
use App\Services\FoodService;
use App\Dtos\Requests\UpdateFoodRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FoodCustomController extends BaseController
{
    public function __construct(
        private readonly FoodService $foodService
    ) {
    }

    #[Route('/food/custom', ['GET'])]
    public function getCustomRecipes(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');

        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        try {
            $foods = $this->foodService->getCustomFoods((int)$userId);

            return $this->json($foods, 200);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    #[Route('/food/custom/{foodId}', ['GET'])]
    public function getRecipe(ServerRequestInterface $request, string $foodId): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');

        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        try {
            $food = $this->foodService->getCustomFoodById((int)$foodId, (int)$userId);

            return $this->json($food, 200);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    #[Route('/food/custom/{foodId}', ['POST'])]
    public function updateRecipe(ServerRequestInterface $request, string $foodId): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');

        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        $body = $this->getJsonBody($request);

        $requiredFields = ['name', 'calories', 'proteins', 'fats', 'carbs'];

        foreach ($requiredFields as $field) {
            if (!isset($body[$field]) || $body[$field] === '') {
                return $this->error("Отсутствует обязательное поле продукта: {$field}");
            }
        }

        try {
            $dto = new UpdateFoodRequest(
                id: (int)$foodId,
                name: $body['name'],
                proteins: (float)$body['proteins'],
                fats: (float)$body['fats'],
                carbs: (float)$body['carbs'],
                calories: (float)$body['calories'],
            );

            $updatedFood = $this->foodService->updateCustomFood($dto, (int)$userId);

            return $this->json([
                'message' => 'Пользовательский продукт успешно обновлен',
                'food' => $updatedFood
            ], 200);

        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    #[Route('/food/custom/{foodId}', ['DELETE'])]
    public function deleteRecipe(ServerRequestInterface $request, string $foodId): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');

        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        try {
            $this->foodService->deleteCustomFood((int)$foodId, (int)$userId);

            return $this->json([
                'message' => 'Пользовательский продукт успешно удален'
            ], 200);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}