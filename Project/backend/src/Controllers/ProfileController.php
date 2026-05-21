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
use App\Enums\FitnessGoal;

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
            return $this->error('Пользователь не авторизован', 401);
        }

        try {
            $user = $this->userService->getProfile((int)$userId);
            $statistics = $this->statisticsService->getProfileStatistics((int)$userId);

            return $this->json([
                'id' => $user->id,
                'name' => $user->name,
                'gender' => $user->gender->value,
                'email' => $user->email,
                'parameters' => $statistics['parameters'] ?? [
                        'weight' => null,
                        'height' => null,
                        'activityLevel' => null,
                        'goal' => null,
                        'measuredAt' => null,
                    ],
                'dailyNorm' => $statistics['dailyNorm'] ?? [
                        'calories' => 0,
                        'proteins' => 0.0,
                        'fats' => 0.0,
                        'carbs' => 0.0,
                        'createdAt' => null,
                    ],
                'monthlyAverage' => $statistics['monthlyAverage'] ?? [
                        'calories' => 0,
                        'proteins' => 0.0,
                        'fats' => 0.0,
                        'carbs' => 0.0,
                    ],
            ], 200);
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

        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        try {
            $profileStatistics = $this->statisticsService->getProfileStatistics((int)$userId);

            return $this->json([
                'weight' => $profileStatistics['parameters']['weight'],
                'height' => $profileStatistics['parameters']['height'],
                'activityLevel' => $profileStatistics['parameters']['activityLevel'],
                'goal' => $profileStatistics['parameters']['goal'],
            ], 200);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    #[Route('/profile/recalculate', ['POST'])]
    public function recalculate(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return $this->error("Пользователь не авторизован", 401);
        }

        $body = $this->getJsonBody($request);

        if (
            empty($body['weight']) ||
            empty($body['height']) ||
            empty($body['activityLevel']) ||
            empty($body['goal'])
        ) {
            return $this->error("Необходимы параметры: weight, height, activityLevel, goal");
        }

        $goal = FitnessGoal::tryFrom($body['goal']);

        if (!$goal) {
            return $this->error("Недопустимое значение goal. Допустимые значения: lose, maintain, gain");
        }

        try {
            $dto = new RecalculateNormsRequest(
                userId: (int)$userId,
                weight: (float)$body['weight'],
                height: (float)$body['height'],
                activityLevel: (float)$body['activityLevel'],
                goal: $goal
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
