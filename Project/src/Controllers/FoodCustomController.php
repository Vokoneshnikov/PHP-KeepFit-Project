<?php

namespace App\Controllers;

use App\Services\FoodService;
use App\Core\Route;
class FoodCustomController {
    public function __construct(
        // private FoodService $foodService,
    ) {}

    #[Route('/food/custom', ['GET'])]
    public function getCustomRecipes() {
        echo "Контроллер: FoodCustom, Метод: getCustomRecipes";
    }
    //ДОЛЖНО БЫТЬ /food/custom/{id} - но это когда добавлю маски
    #[Route('/food/custom/{foodId}',  ['GET'])]
    public function getRecipe(int $foodId) {
        echo "Контроллер: FoodCustom, Метод: getRecipe";
    }
    #[Route('/food/custom/{foodId}',  ['POST'])]
    public function updateRecipe(int $foodId) {
        echo "Контроллер: FoodCustom, Метод: updateRecipe";
    }
    #[Route('/food/custom/{foodId}', ['DELETE'])]
    public function deleteRecipe(int $foodId) {
        echo "Контроллер: FoodCustom, Метод: deleteRecipe";
    }

}


// FoodCustomController:
// GET /food/custom - получение списка своих рецептов пищи
// GET /food/custom?id={id} получение конкретного рецепта
// POST /food/custom?id={id} обновление конкретного рецепта
// DELETE /food/custom?id={id} - удаление рецепта