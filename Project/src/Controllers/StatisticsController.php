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
        private readonly StatisticsService $statsService
    ) {}

    #[Route('/stats/weekly', ['GET'])]
    public function showWeeklyStats(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return new Response(401, ['Content-Type' => 'application/json'], json_encode([
                'error' => 'Пользователь не авторизован'
            ]));
        }

        try {
            $data = $this->statsService->getWeeklyData((int)$userId);
            return new Response(200, ['Content-Type' => 'application/json'], json_encode($data));
        } catch (\Exception $e) {
            return new Response(500, ['Content-Type' => 'application/json'], json_encode([
                'error' => 'Ошибка сервера: ' . $e->getMessage()
            ]));
        }
    }

    #[Route('/stats/monthly', ['GET'])]
    public function showMonthlyStats(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return new Response(401, ['Content-Type' => 'application/json'], json_encode([
                'error' => 'Пользователь не авторизован'
            ]));
        }

        try {
            $data = $this->statsService->getMonthlyData((int)$userId);
            return new Response(200, ['Content-Type' => 'application/json'], json_encode($data));
        } catch (\Exception $e) {
            return new Response(500, ['Content-Type' => 'application/json'], json_encode([
                'error' => 'Ошибка сервера: ' . $e->getMessage()
            ]));
        }
    }
}