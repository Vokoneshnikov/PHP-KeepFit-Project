<?php

namespace App\Controllers;

use App\Core\Route;
use App\Services\MealService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MealController extends BaseController
{
    // Внедряем MealService для управления записями дневника
    public function __construct(
        private readonly MealService $mealService
    ) {}

    /**
     * GET /meals/{mealId}
     * Получение информации о конкретной записи в дневнике питания
     */
    #[Route('/meals/{mealId}', ['GET'])]
    public function getProductInfo(ServerRequestInterface $request, string $mealId): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        try {
            $mealItem = $this->mealService->getMealLogById((int)$mealId);

            if (!$mealItem) {
                return $this->error("Запись в дневнике не найдена", 404);
            }

            // Защита данных: проверям, что эта запись принадлежит именно текущему юзеру
            if ($mealItem->userId !== (int)$userId) {
                return $this->error("Доступ запрещен", 403);
            }

            return $this->json($mealItem, 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * POST /meals/{mealId}
     * Обновление существующей записи (например, изменение веса съеденного продукта)
     */
    #[Route('/meals/{mealId}', ['POST'])]
    public function updateProductInfo(ServerRequestInterface $request, string $mealId): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        $body = $this->getJsonBody($request);

        if (empty($body['weight']) || (float)$body['weight'] <= 0) {
            return $this->error("Вес порции должен быть положительным числом", 400);
        }

        try {
            // Сервис проверит права владения, пересчитает КБЖУ с учетом нового веса порции и обновит БД
            $updatedMeal = $this->mealService->updateMealLogWeight(
                mealId: (int)$mealId,
                userId: (int)$userId,
                newWeight: (float)$body['weight']
            );

            return $this->json([
                'message' => 'Запись дневника успешно обновлена',
                'meal' => $updatedMeal
            ], 200);

        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->error("Не удалось обновить запись приема пищи", 400);
        }
    }

    /**
     * DELETE /meals/{mealId}
     * Удаление продукта из дневника питания
     */
    #[Route('/meals/{mealId}', ['DELETE'])]
    public function deleteProduct(ServerRequestInterface $request, string $mealId): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        try {
            // Передаем id записи и id юзера для безопасного удаления
            $this->mealService->deleteMealLogEntry((int)$mealId, (int)$userId);

            return $this->json([
                'message' => 'Продукт успешно удален из дневника питания'
            ], 200);

        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->error("Ошибка при удалении записи", 400);
        }
    }
}