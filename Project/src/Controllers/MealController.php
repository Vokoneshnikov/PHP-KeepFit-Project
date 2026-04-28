<?php

namespace App\Controllers;

use App\Services\MealService;
use App\Core\Route;
class MealController {
    public function __construct(
        // private MealService $mealService,
    ) {}

    #[Route('/meals/{mealId}', ['GET'])]
    public function getProductInfo(int $mealId) {
        echo "Контроллер: Meal, Метод: getProductInfo";
    }
    #[Route('/meals/{mealId}',  ['POST'])]
    public function updateProductInfo(int $mealId) {
        echo "Контроллер: Meal, Метод: updateProductInfo";
    }
    #[Route('/meals/{mealId}',  ['DELETE'])]
    public function deleteProduct(int $mealId) {
        echo "Контроллер: Meal, Метод: deleteProduct";
    }
}

// MealController:
// GET /meals?id={id} - получение страницы конкретного продукта
// POST /meals?id={id} - обновление граммовки/рациона
// DELETE /meals?id={id} - удаление пищи из приема пищи