<?php

namespace App\Services;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

class DiaryService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getDiaryForDate(int $userId, string $date): array
    {
        $this->validateDate($date);

        try {
            $dailyNorm = $this->getLatestDailyNorm($userId);
            $mealItems = $this->getMealItemsForDate($userId, $date);

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

                $calculated = [
                    'calories' => round(((float)$item['calories'] * (float)$item['amount_grams']) / 100, 1),
                    'proteins' => round(((float)$item['proteins'] * (float)$item['amount_grams']) / 100, 1),
                    'fats' => round(((float)$item['fats'] * (float)$item['amount_grams']) / 100, 1),
                    'carbs' => round(((float)$item['carbs'] * (float)$item['amount_grams']) / 100, 1),
                ];

                $totals['calories'] += $calculated['calories'];
                $totals['proteins'] += $calculated['proteins'];
                $totals['fats'] += $calculated['fats'];
                $totals['carbs'] += $calculated['carbs'];

                $groupedMeals[$mealType][] = [
                    'mealId' => (int)$item['meal_id'],
                    'foodId' => (int)$item['food_id'],
                    'name' => $item['food_name'],
                    'amountGrams' => (float)$item['amount_grams'],
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
                    'percent' => $dailyNorm['calories'] > 0
                        ? round(($totals['calories'] / $dailyNorm['calories']) * 100, 1)
                        : 0,
                ],
                'meals' => $groupedMeals,
            ];

        } catch (PDOException $e) {
            $this->logger->error("Ошибка DiaryService::getDiaryForDate", [
                'exception' => $e->getMessage()
            ]);

            throw new \Exception("Не удалось получить дневник питания: " . $e->getMessage());
        }
    }

    private function getLatestDailyNorm(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT calories, proteins, fats, carbs
            FROM daily_norms
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT 1
        ");

        $stmt->execute([
            'user_id' => $userId
        ]);

        $norm = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$norm) {
            return [
                'calories' => 0,
                'proteins' => 0.0,
                'fats' => 0.0,
                'carbs' => 0.0,
            ];
        }

        return [
            'calories' => (int)$norm['calories'],
            'proteins' => round((float)$norm['proteins'], 1),
            'fats' => round((float)$norm['fats'], 1),
            'carbs' => round((float)$norm['carbs'], 1),
        ];
    }

    private function getMealItemsForDate(int $userId, string $date): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                m.id AS meal_id,
                m.food_id,
                m.amount_grams,
                m.meal_type,
                f.name AS food_name,
                f.calories,
                f.proteins,
                f.fats,
                f.carbs
            FROM meals m
            JOIN foods f ON f.id = m.food_id
            WHERE m.user_id = :user_id
              AND m.consumed_at = :date
            ORDER BY m.id ASC
        ");

        $stmt->execute([
            'user_id' => $userId,
            'date' => $date,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function validateDate(string $date): void
    {
        $parsedDate = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            throw new \InvalidArgumentException("Некорректная дата. Используйте формат YYYY-MM-DD");
        }
    }
}