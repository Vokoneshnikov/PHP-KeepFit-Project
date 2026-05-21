<?php

namespace App\Repositories\Implementations;

use App\Core\Database;
use App\Core\LoggerFactory;
use App\Repositories\Interfaces\IDiaryRepository;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

class DiaryRepository implements IDiaryRepository
{
    private PDO $pdo;

    private LoggerInterface $logger;

    public function __construct(?PDO $pdo = null, ?LoggerInterface $logger = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->logger = $logger ?? LoggerFactory::create();
    }

    public function getLatestDailyNorm(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT calories, proteins, fats, carbs
                FROM daily_norms
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT 1
            ");

            $stmt->execute([
                'user_id' => $userId,
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
        } catch (PDOException $e) {
            $msg = 'Ошибка DiaryRepository::getLatestDailyNorm';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception('Не удалось получить дневную норму КБЖУ');
        }
    }

    public function getMealItemsForDate(int $userId, string $date): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    m.id AS meal_id,
                    m.food_id,
                    m.amount_grams,
                    m.meal_type::text AS meal_type,
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
        } catch (PDOException $e) {
            $msg = 'Ошибка DiaryRepository::getMealItemsForDate';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception('Не удалось получить продукты дневника питания');
        }
    }
}