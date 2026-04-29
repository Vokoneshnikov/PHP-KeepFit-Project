<?php

namespace App\Controllers;

use App\Core\Route;
use App\Services\MealService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;

class MealController
{
    public function __construct(
        // private MealService $mealService,
    ) {
    }

    #[Route('/meals/{mealId}', ['GET'])]
    public function getProductInfo(ServerRequestInterface $request, string $mealId): ResponseInterface
    {
        return new Response(200, [], "Контроллер: Meal, Метод: getProductInfo, ID: " . $mealId);
    }

    #[Route('/meals/{mealId}', ['POST'])]
    public function updateProductInfo(ServerRequestInterface $request, string $mealId): ResponseInterface
    {
        return new Response(200, [], "Контроллер: Meal, Метод: updateProductInfo, ID: " . $mealId);
    }

    #[Route('/meals/{mealId}', ['DELETE'])]
    public function deleteProduct(ServerRequestInterface $request, string $mealId): ResponseInterface
    {
        return new Response(200, [], "Контроллер: Meal, Метод: deleteProduct, ID: " . $mealId);
    }
}
