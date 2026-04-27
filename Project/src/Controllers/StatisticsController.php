<?php

namespace App\Controllers;

use App\Services\StatisticsService;
use App\Core\Route;
class StatisticsController {
    public function __construct(
        // private StatisticsService $statsService,
    ) {}

    #[Route('/stats/weekly', ['GET'])]
    public function showWeeklyStats() {
        echo "Контроллер: Statistics, Метод: showWeeklyStats";
    }
    #[Route('/stats/monthly', ['GET'])]
    public function showMonthlyStats() {
        echo "Контроллер: Statistics, Метод: showMonthlyStats";
    }

}

// StatisticsController:
// GET /stats/weekly - получение статистики за неделю
// GET /stats/monthly - получение статистики за месяц