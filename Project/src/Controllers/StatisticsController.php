<?php

namespace App\Controllers;

use App\Core\Route;
use App\Services\StatisticsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;

class StatisticsController
{
    public function __construct(
        // private StatisticsService $statsService,
    ) {
    }

    #[Route('/stats/weekly', ['GET'])]
    public function showWeeklyStats(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], "Контроллер: Statistics, Метод: showWeeklyStats");
    }

    #[Route('/stats/monthly', ['GET'])]
    public function showMonthlyStats(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], "Контроллер: Statistics, Метод: showMonthlyStats");
    }
}
