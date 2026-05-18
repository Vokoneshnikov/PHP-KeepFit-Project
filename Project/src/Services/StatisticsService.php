<?php

namespace App\Services;

use App\Dtos\Requests\RecalculateNormsRequest;
use App\Dtos\Responses\DailyNormsResponse;
use App\Enums\Gender;

class StatisticsService
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function calculateAndSaveNorms(RecalculateNormsRequest $request): DailyNormsResponse
    {
        // 1. Получаем пользователя для доступа к полу и дате рождения
        $user = $this->userService->getProfile($request->userId);

        // 2. Вычисляем возраст пользователя
        $now = new \DateTimeImmutable();
        $age = $now->diff($user->birthDate)->y;

        // 3. Формула Миффлина-Сан Жеора (BMR)
        $bmr = (10 * $request->weight) + (6.25 * $request->height) - (5 * $age);
        $bmr += ($user->gender === Gender::Male) ? 5 : -161;

        // 4. Учитываем коэффициент активности (TDEE)
        $tdee = $bmr * $request->activityLevel;

        // 5. Расчет БЖУ (Белки: 30%, Жиры: 30%, Углеводы: 40%)
        // 1г белка = 4 ккал, 1г жира = 9 ккал, 1г углеводов = 4 ккал
        $proteins = ($tdee * 0.3) / 4;
        $fats = ($tdee * 0.3) / 9;
        $carbs = ($tdee * 0.4) / 4;

        $response = new DailyNormsResponse(
            userId: $request->userId,
            dailyCalories: (int)round($tdee),
            proteins: round($proteins, 1),
            fats: round($fats, 1),
            carbs: round($carbs, 1)
        );

        // TODO: Здесь мы вызовем $this->normsRepository->save(...)
        // чтобы сохранить обновленные вес, рост и норму в базу данных

        return $response;
    }
}