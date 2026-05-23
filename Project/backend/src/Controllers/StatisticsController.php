<?php

namespace App\Controllers;

use App\Core\Route;
use App\Services\StatisticsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class StatisticsController extends BaseController
{
    public function __construct(
        private readonly StatisticsService $statsService
    ) {
    }

    #[Route('/stats/weekly', ['GET'])]
    public function showWeeklyStats(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return $this->error('Пользователь не авторизован', 401);
        }

        try {
            $data = $this->statsService->getWeeklyData((int)$userId);

            return $this->json($data, 200);
        } catch (\Exception $e) {
            return $this->error('Ошибка сервера: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/stats/monthly', ['GET'])]
    public function showMonthlyStats(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return $this->error('Пользователь не авторизован', 401);
        }

        try {
            $data = $this->statsService->getMonthlyData((int)$userId);

            return $this->json($data, 200);
        } catch (\Exception $e) {
            return $this->error('Ошибка сервера: ' . $e->getMessage(), 500);
        }
    }
}
