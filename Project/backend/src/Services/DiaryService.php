<?php

namespace App\Services;

use App\Repositories\Interfaces\IDiaryRepository;
use Psr\Log\LoggerInterface;

class DiaryService
{
    public function __construct(
        private readonly IDiaryRepository $diaryRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getDiaryForDate(int $userId, string $date): array
    {
        $this->validateDate($date);

        try {
            $dailyNorm = $this->diaryRepository->getLatestDailyNorm($userId);
            $mealItems = $this->diaryRepository->getMealItemsForDate($userId, $date);

            $groupedMeals = [
                'breakfast' => [],
                'lunch' => [],
                'dinner' => [],
                'other' => [],
            ];

            $totals = [
                'calories' => 0.0,
                'proteins' => 0.0,
                'fats' => 0.0,
                'carbs' => 0.0,
            ];

            foreach ($mealItems as $item) {
                $mealType = $item['meal_type'];

                if (!array_key_exists($mealType, $groupedMeals)) {
                    $mealType = 'other';
                }

                $amountGrams = (float)$item['amount_grams'];

                $calculated = [
                    'calories' => round(((float)$item['calories'] * $amountGrams) / 100, 1),
                    'proteins' => round(((float)$item['proteins'] * $amountGrams) / 100, 1),
                    'fats' => round(((float)$item['fats'] * $amountGrams) / 100, 1),
                    'carbs' => round(((float)$item['carbs'] * $amountGrams) / 100, 1),
                ];

                $totals['calories'] += $calculated['calories'];
                $totals['proteins'] += $calculated['proteins'];
                $totals['fats'] += $calculated['fats'];
                $totals['carbs'] += $calculated['carbs'];

                $groupedMeals[$mealType][] = [
                    'mealId' => (int)$item['meal_id'],
                    'foodId' => (int)$item['food_id'],
                    'name' => $item['food_name'],
                    'amountGrams' => $amountGrams,
                    'cpfc' => $calculated,
                ];
            }

            $totals = [
                'calories' => round($totals['calories'], 1),
                'proteins' => round($totals['proteins'], 1),
                'fats' => round($totals['fats'], 1),
                'carbs' => round($totals['carbs'], 1),
            ];

            return [
                'date' => $date,
                'dailyNorm' => $dailyNorm,
                'totals' => $totals,
                'caloriesRatio' => [
                    'consumed' => $totals['calories'],
                    'target' => $dailyNorm['calories'],
                    'remaining' => max($dailyNorm['calories'] - $totals['calories'], 0),
                    'percent' => $dailyNorm['calories'] > 0
                        ? round(($totals['calories'] / $dailyNorm['calories']) * 100, 1)
                        : 0,
                ],
                'meals' => $groupedMeals,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Ошибка DiaryService::getDiaryForDate', [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception('Не удалось получить дневник питания: ' . $e->getMessage());
        }
    }

    private function validateDate(string $date): void
    {
        $parsedDate = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            throw new \InvalidArgumentException("Некорректная дата. Используйте формат YYYY-MM-DD");
        }
    }
}
