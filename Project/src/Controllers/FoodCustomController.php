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
    #[Route('/food/custom/detail',  ['GET'])]
    public function getRecipe() {
        echo "Контроллер: FoodCustom, Метод: getRecipe";
    }
    #[Route('/food/custom',  ['POST'])]
    public function updateRecipe() {
        echo "Контроллер: FoodCustom, Метод: updateRecipe";
    }
    #[Route('/food/custom', ['DELETE'])]
    public function deleteRecipe() {
        echo "Контроллер: FoodCustom, Метод: deleteRecipe";
    }

}


// FoodCustomController:
// GET /food/custom - получение списка своих рецептов пищи
// GET /food/custom?id={id} получение конкретного рецепта
// POST /food/custom?id={id} обновление конкретного рецепта
// DELETE /food/custom?id={id} - удаление рецепта