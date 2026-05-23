<?php

namespace App\Controllers;

use App\Core\Route;
use App\Services\FoodService;
use App\Services\MealService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FoodController extends BaseController
{
    // Внедряем FoodService для работы с продуктами и MealService для добавления в дневник
    public function __construct(
        private readonly FoodService $foodService,
        private readonly MealService $mealService
    ) {
    }

    /**
     * GET /food
     * Главная страница поиска (для API возвращает базовый дефолтный список продуктов)
     */
    #[Route('/food', ['GET'])]
    public function getSearchMainPage(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $defaultFoods = $this->foodService->getDefaultFoods();
            return $this->json($defaultFoods, 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * GET /food/search
     * Поиск продуктов по строке запроса ?q=...
     */
    #[Route('/food/search', ['GET'])]
    public function getSearchResults(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams()['q'] ?? '';

        if (empty($query)) {
            return $this->error("Поисковый запрос не может быть пустым", 400);
        }

        try {
            $results = $this->foodService->searchFoods($query);
            return $this->json($results, 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * GET /food/recent
     * Возвращает список продуктов, которые данный пользователь ел недавно
     */
    #[Route('/food/recent', ['GET'])]
    public function getRecentFood(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        try {
            $recentFoods = $this->foodService->getRecentFoods((int)$userId);
            return $this->json($recentFoods, 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * GET /food/add
     * Мета-данные для формы создания продукта (например, категории).
     * Для SPA отдаем просто пустой шаблон структуры
     */
    #[Route('/food/add', ['GET'])]
    public function getCreateForm(ServerRequestInterface $request): ResponseInterface
    {
        return $this->json([
            'allowed_categories' => ['Common', 'Drinks', 'Snacks'],
            'rules' => ['name' => 'required', 'calories' => 'required']
        ], 200);
    }

    /**
     * POST /food/add
     * Создание нового кастомного продукта в базе данных
     */
    #[Route('/food/add', ['POST'])]
    public function storeProduct(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        $body = $this->getJsonBody($request);

        // Строгая валидация КБЖУ
        $requiredFields = ['name', 'calories', 'proteins', 'fats', 'carbs'];
        foreach ($requiredFields as $field) {
            if (!isset($body[$field]) || $body[$field] === '') {
                return $this->error("Отсутствует обязательное поле продукта: {$field}");
            }
        }

        try {
            // Формируем DTO вместо сырого массива
            $dto = new \App\Dtos\Requests\CreateFoodRequest(
                $body['name'],
                (float)$body['calories'],
                (float)$body['proteins'],
                (float)$body['fats'],
                (float)$body['carbs'],
                (int)$userId
            );
            // Вызываем правильный метод сервиса
            $newProduct = $this->foodService->createFood($dto);

            return $this->json($newProduct, 201);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->error("Не удалось сохранить продукт", 400);
        }
    }

    /**
     * GET /food/{foodId}
     * Получение детальной информации о конкретном продукте по ID
     */
    #[Route('/food/{foodId}', ['GET'])]
    public function getProductPage(ServerRequestInterface $request, string $foodId): ResponseInterface
    {
        try {
            $product = $this->foodService->getProductById((int)$foodId);
            if (!$product) {
                return $this->error("Продукт с ID {$foodId} не найден", 404);
            }
            return $this->json($product, 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * POST /food/{foodId}
     * Добавление существующего продукта в дневник питания пользователя (создание приема пищи)
     */
    #[Route('/food/{foodId}', ['POST'])]
    public function addProduct(ServerRequestInterface $request, string $foodId): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        $body = $this->getJsonBody($request);

        // Для добавления в дневник критически важен вес порции
        if (empty($body['weight']) || (float)$body['weight'] <= 0) {
            return $this->error("Необходимо указать корректный вес порции в граммах (weight)", 400);
        }

        try {
            // Вызываем MealService для логирования приема пищи
            $mealItem = $this->mealService->addFoodToLog(
                userId: (int)$userId,
                foodId: (int)$foodId,
                weight: (float)$body['weight'],
                mealType: $body['mealType'] ?? 'Breakfast' // breakfast, lunch, dinner, snack
            );

            return $this->json([
                'message' => 'Продукт успешно добавлен в дневник питания',
                'meal' => $mealItem
            ], 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
