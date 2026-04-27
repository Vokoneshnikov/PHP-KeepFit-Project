<?php

namespace App\Controllers;

use App\Services\FoodService;
use App\Core\Route;
class FoodController {
    public function __construct(
        // private FoodService $foodService,
    ) {}

    #[Route('/food', ['GET'])]
    public function getProductPage() {
        echo "Контроллер: Food, Метод: getProductPage";
    }
    #[Route('/food',  ['POST'])]
    public function addProduct() {
        echo "Контроллер: Food, Метод: addProduct";
    }
    #[Route('/food/search',  ['GET'])]
    public function getSearchResults() {
        echo "Контроллер: Food, Метод: getSearchResults";
    }
    #[Route('/food/recent', ['GET'])]
    public function getRecentFood() {
        echo "Контроллер: Food, Метод: getRecentFood";
    }
    #[Route('/food/add',  ['GET'])]
    public function getCreateForm() {
        echo "Контроллер: Food, Метод: getCreateForm";
    }
    #[Route('/food/add',  ['POST'])]
    public function storeProduct() {
        echo "Контроллер: Food, Метод: storeProduct";
    }

}

// FoodController:
// GET /food?id={id} - получение страницы конкретного продукта для его добавления в рацион
// POST /food?id={id} - добавление продукта в рацион
// GET /food/search - получение списка пищи по ключевому слову
// GET /food/recent -  получение списка недавней пищи
// GET /food/add - получение формы для добавления нового рецепта
// POST /food/add - отправка данных из формы