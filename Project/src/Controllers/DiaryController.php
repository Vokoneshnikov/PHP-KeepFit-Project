<?php

namespace App\Controllers;

use App\Services\DiaryService;
use App\Core\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;
class DiaryController
{
    public function __construct(
        // private DiaryService $diaryService,
    ) {
    }
    #[Route('/diary', ['GET'])]
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $date = $queryParams['date'] ?? 'не указана';

        $body = "Контроллер: Diary, Метод: index. Дата: " . $date;

        return new Response(200, [], $body);
    }
}

// DiaryController:
// GET /diary?date={date} - получение информации для дневника за этот день(конкретные приемы пищи с содержимым)
