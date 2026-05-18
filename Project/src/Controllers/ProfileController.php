<?php

namespace App\Controllers;

use App\Core\Route;
use App\Services\UserService;
use App\Services\StatisticsService;
use App\Dtos\Requests\UpdateUserRequest;
use App\Dtos\Requests\RecalculateNormsRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Enums\Gender;

class ProfileController extends BaseController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly StatisticsService $statisticsService
    ) {}

    #[Route('/profile', ['GET'])]
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        try {
            $user = $this->userService->getProfile((int)$userId);
            return $this->json($user, 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    #[Route('/profile/edit', ['GET'])]
    public function editProfileInfo(ServerRequestInterface $request): ResponseInterface
    {
        return $this->index($request);
    }

    #[Route('/profile/edit', ['POST'])]
    public function updateProfileInfo(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        $body = $this->getJsonBody($request);

        try {
            $dto = new UpdateUserRequest(
                id: (int)$userId,
                email: $body['email'] ?? null,
                name: $body['name'] ?? null,
                gender: isset($body['gender']) ? Gender::tryFrom($body['gender']) : null,
                birthDate: isset($body['birthDate']) ? new \DateTimeImmutable($body['birthDate']) : null
            );

            $updatedUser = $this->userService->updateProfile($dto);
            return $this->json($updatedUser, 200);

        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->error("Ошибка при обновлении профиля", 400);
        }
    }

    #[Route('/profile/recalculate', ['GET'])]
    public function editRecalculationInfo(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        //TODO:
        // В будущем этот метод должен возвращать последние сохраненные параметры
        // из таблицы пользовательских норм (UserNorms), чтобы фронтенд мог предзаполнить форму.
        return $this->json([
            'weight' => null,
            'height' => null,
            'activityLevel' => 1.2
        ], 200);
    }

    #[Route('/profile/recalculate', ['POST'])]
    public function recalculate(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        $body = $this->getJsonBody($request);

        if (empty($body['weight']) || empty($body['height']) || empty($body['activityLevel'])) {
            return $this->error("Необходимы параметры: weight, height, activityLevel");
        }

        try {
            $dto = new RecalculateNormsRequest(
                userId: (int)$userId,
                weight: (float)$body['weight'],
                height: (float)$body['height'],
                activityLevel: (float)$body['activityLevel']
            );

            $calculatedNorms = $this->statisticsService->calculateAndSaveNorms($dto);

            return $this->json([
                'message' => 'Норма КБЖУ успешно пересчитана',
                'norm' => $calculatedNorms
            ], 200);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
