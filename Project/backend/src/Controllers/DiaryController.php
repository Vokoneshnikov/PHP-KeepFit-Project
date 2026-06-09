<?php

namespace App\Controllers;

use App\Core\Route;
use App\Services\DiaryService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DiaryController extends BaseController
{
    public function __construct(
        private readonly DiaryService $diaryService
    ) {
    }

    #[Route('/diary', ['GET'])]
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');

        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        $queryParams = $request->getQueryParams();

        $date = $queryParams['date'] ?? date('Y-m-d');

        try {
            $diary = $this->diaryService->getDiaryForDate((int)$userId, $date);

            return $this->json($diary, 200);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
