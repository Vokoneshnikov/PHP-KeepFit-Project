<?php

namespace App\Controllers;

use App\Services\MealService;
use App\Core\Route;
class MealController {
    public function __construct(
        // private MealService $mealService,
    ) {}

    #[Route('/meals', ['GET'])]
    public function getProductInfo() {
        echo "Контроллер: Meal, Метод: getProductInfo";
    }
    #[Route('/meals',  ['POST'])]
    public function updateProductInfo() {
        echo "Контроллер: Meal, Метод: updateProductInfo";
    }
    #[Route('/meals',  ['DELETE'])]
    public function deleteProduct() {
        echo "Контроллер: Meal, Метод: deleteProduct";
    }
}

// MealController:
// GET /meals?id={id} - получение страницы конкретного продукта
// POST /meals?id={id} - обновление граммовки/рациона
// DELETE /meals?id={id} - удаление пищи из приема пищи