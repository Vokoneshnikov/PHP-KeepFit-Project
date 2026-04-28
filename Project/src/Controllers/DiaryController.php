<?php

namespace App\Controllers;

use App\Services\DiaryService;
use App\Core\Route;

class DiaryController
{
    public function __construct(
        // private DiaryService $diaryService,
    ) {
    }
    #[Route('/diary', ['GET'])]
    public function index()
    {
        echo "Контроллер: Diary, Метод: index. Дата: " . ($_GET['date'] ?? 'не указана');
    }
}

// DiaryController:
// GET /diary?date={date} - получение информации для дневника за этот день(конкретные приемы пищи с содержимым)
