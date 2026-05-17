<?php

namespace App\Controllers;

use App\Core\Route;
use App\Services\FoodService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;

class FoodCustomController
{
    public function __construct(
        // private FoodService $foodService,
    ) {
    }

    #[Route('/food/custom', ['GET'])]
    public function getCustomRecipes(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], "Контроллер: FoodCustom, Метод: getCustomRecipes");
    }

    #[Route('/food/custom/{foodId}', ['GET'])]
    public function getRecipe(ServerRequestInterface $request, string $foodId): ResponseInterface
    {
        return new Response(200, [], "Контроллер: FoodCustom, Метод: getRecipe, ID: " . $foodId);
    }

    #[Route('/food/custom/{foodId}', ['POST'])]
    public function updateRecipe(ServerRequestInterface $request, string $foodId): ResponseInterface
    {
        return new Response(200, [], "Контроллер: FoodCustom, Метод: updateRecipe, ID: " . $foodId);
    }

    #[Route('/food/custom/{foodId}', ['DELETE'])]
    public function deleteRecipe(ServerRequestInterface $request, string $foodId): ResponseInterface
    {
        return new Response(200, [], "Контроллер: FoodCustom, Метод: deleteRecipe, ID: " . $foodId);
    }
}
