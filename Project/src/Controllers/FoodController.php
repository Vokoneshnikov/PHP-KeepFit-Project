<?php

namespace App\Controllers;

use App\Core\Route;
use App\Services\FoodService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;

class FoodController
{
    public function __construct(
        // private FoodService $foodService,
    ) {
    }

    #[Route('/food', ['GET'])]
    public function getSearchMainPage(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], "Контроллер: Food, Метод: getSearchMainPage");
    }

    #[Route('/food/search', ['GET'])]
    public function getSearchResults(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams()['q'] ?? '';
        return new Response(200, [], "Контроллер: Food, Метод: getSearchResults, Поиск: " . $query);
    }

    #[Route('/food/recent', ['GET'])]
    public function getRecentFood(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], "Контроллер: Food, Метод: getRecentFood");
    }

    #[Route('/food/add', ['GET'])]
    public function getCreateForm(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], "Контроллер: Food, Метод: getCreateForm");
    }

    #[Route('/food/add', ['POST'])]
    public function storeProduct(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(201, [], "Контроллер: Food, Метод: storeProduct");
    }

    /* Динамические роуты в конце, чтобы не перехватывать статику */

    #[Route('/food/{foodId}', ['GET'])]
    public function getProductPage(ServerRequestInterface $request, string $foodId): ResponseInterface
    {
        return new Response(200, [], "Контроллер: Food, Метод: getProductPage, ID: " . $foodId);
    }

    #[Route('/food/{foodId}', ['POST'])]
    public function addProduct(ServerRequestInterface $request, string $foodId): ResponseInterface
    {
        return new Response(201, [], "Контроллер: Food, Метод: addProduct, ID: " . $foodId);
    }
}
