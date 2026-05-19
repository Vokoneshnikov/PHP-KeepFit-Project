<?php

namespace App\Repositories\Implementations;

use App\Repositories\Interfaces\IStatisticsRepository;
use App\Core\Database;
use App\Core\LoggerFactory;
use Psr\Log\LoggerInterface;
use PDO;
use PDOException;

class StatisticsRepository implements IStatisticsRepository
{
    private readonly PDO $pdo;
    private readonly LoggerInterface $logger;

    public function __construct(?PDO $pdo = null, ?LoggerInterface $logger = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->logger = $logger ?? LoggerFactory::create();
    }

    /**
     * Сохраняет рассчитанную норму КБЖУ в таблицу daily_norms
     */
    public function saveDailyNorm(int $userId, int $calories, float $proteins, float $fats, float $carbs): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO daily_norms (user_id, calories, proteins, fats, carbs)
                VALUES (:user_id, :calories, :proteins, :fats, :carbs)
            ");

            $stmt->execute([
                'user_id'  => $userId,
                'calories' => $calories,
                'proteins' => $proteins,
                'fats'     => $fats,
                'carbs'    => $carbs
            ]);
        } catch (PDOException $e) {
            $this->logger->error("Ошибка StatisticsRepository::saveDailyNorm", ['exception' => $e->getMessage()]);
            throw new \Exception("Не удалось сохранить дневные нормы в БД");
        }
    }

    /**
     * Сохраняет текущие параметры тела пользователя в таблицу user_parameters
     */
    public function saveUserParameters(int $userId, float $weight, int $height, string $activityLevel, string $goal): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_parameters (user_id, weight, height, activity_factor, goal)
                VALUES (:user_id, :weight, :height, :activity_factor, :goal)
            ");

            $stmt->execute([
                'user_id'         => $userId,
                'weight'          => $weight,
                'height'          => $height,
                'activity_factor' => $activityLevel, // Запись в Postgres-enum activity_level
                'goal'            => $goal           // Запись в Postgres-enum fitness_goal
            ]);
        } catch (PDOException $e) {
            $this->logger->error("Ошибка StatisticsRepository::saveUserParameters", ['exception' => $e->getMessage()]);
            throw new \Exception("Не удалось сохранить параметры пользователя в БД");
        }
    }

    /**
     * Получает статистику по дням за текущую неделю (с понедельника по воскресенье)
     */
    public function getWeeklyProgress(int $userId): array
    {
        try {
            $query = "
                WITH RECURSIVE days AS (
                    SELECT DATE_TRUNC('week', CURRENT_DATE)::date AS day
                    UNION ALL
                    SELECT (day + 1)::date FROM days WHERE day < DATE_TRUNC('week', CURRENT_DATE)::date + 6
                )
                SELECT 
                    days.day AS date,
                    COALESCE(SUM((m.amount_grams / 100.0) * f.calories), 0)::integer AS consumed_calories,
                    COALESCE(SUM((m.amount_grams / 100.0) * f.proteins), 0)::float AS consumed_proteins,
                    COALESCE(SUM((m.amount_grams / 100.0) * f.fats), 0)::float AS consumed_fats,
                    COALESCE(SUM((m.amount_grams / 100.0) * f.carbs), 0)::float AS consumed_carbs
                FROM days
                LEFT JOIN meals m ON m.consumed_at = days.day AND m.user_id = :user_id
                LEFT JOIN foods f ON m.food_id = f.id
                GROUP BY days.day
                ORDER BY days.day ASC
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['user_id' => $userId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error("Ошибка StatisticsRepository::getWeeklyProgress", ['exception' => $e->getMessage()]);
            throw new \Exception("Ошибка при генерации недельной выборки");
        }
    }

    /**
     * Получает статистику по дням за текущий календарный месяц (с 1 числа по конец месяца)
     */
    public function getMonthlyProgress(int $userId): array
    {
        try {
            $query = "
                WITH RECURSIVE days AS (
                    SELECT DATE_TRUNC('month', CURRENT_DATE)::date AS day
                    UNION ALL
                    SELECT (day + 1)::date FROM days WHERE day < (DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month' - INTERVAL '1 day')::date
                )
                SELECT 
                    days.day AS date,
                    COALESCE(SUM((m.amount_grams / 100.0) * f.calories), 0)::integer AS consumed_calories,
                    COALESCE(SUM((m.amount_grams / 100.0) * f.proteins), 0)::float AS consumed_proteins,
                    COALESCE(SUM((m.amount_grams / 100.0) * f.fats), 0)::float AS consumed_fats,
                    COALESCE(SUM((m.amount_grams / 100.0) * f.carbs), 0)::float AS consumed_carbs
                FROM days
                LEFT JOIN meals m ON m.consumed_at = days.day AND m.user_id = :user_id
                LEFT JOIN foods f ON m.food_id = f.id
                GROUP BY days.day
                ORDER BY days.day ASC
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['user_id' => $userId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error("Ошибка StatisticsRepository::getMonthlyProgress", ['exception' => $e->getMessage()]);
            throw new \Exception("Ошибка при генерации месячной выборки");
        }
    }

    /**
     * Получает усредненные показатели КБЖУ за период (неделя или месяц)
     * и сопоставляет их с последней установленной нормой
     */
    public function getAveragesAndNorms(int $userId, string $period = 'week'): array
    {
        try {
            $interval = $period === 'month' ? "DATE_TRUNC('month', CURRENT_DATE)" : "DATE_TRUNC('week', CURRENT_DATE)";

            $query = "
                SELECT 
                    AVG(daily_totals.total_calories)::integer AS avg_calories,
                    AVG(daily_totals.total_proteins)::float AS avg_proteins,
                    AVG(daily_totals.total_fats)::float AS avg_fats,
                    AVG(daily_totals.total_carbs)::float AS avg_carbs,
                    (SELECT calories FROM daily_norms WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1) AS target_calories,
                    (SELECT proteins FROM daily_norms WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1) AS target_proteins,
                    (SELECT fats FROM daily_norms WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1) AS target_fats,
                    (SELECT carbs FROM daily_norms WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1) AS target_carbs
                FROM (
                    SELECT 
                        m.consumed_at,
                        SUM((m.amount_grams / 100.0) * f.calories) AS total_calories,
                        SUM((m.amount_grams / 100.0) * f.proteins) AS total_proteins,
                        SUM((m.amount_grams / 100.0) * f.fats) AS total_fats,
                        SUM((m.amount_grams / 100.0) * f.carbs) AS total_carbs
                    FROM meals m
                    JOIN foods f ON m.food_id = f.id
                    WHERE m.user_id = :user_id AND m.consumed_at >= $interval
                    GROUP BY m.consumed_at
                ) AS daily_totals
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'avg' => [
                    'calories' => $result['avg_calories'] ?? 0,
                    'proteins' => round($result['avg_proteins'] ?? 0, 1),
                    'fats'     => round($result['avg_fats'] ?? 0, 1),
                    'carbs'    => round($result['avg_carbs'] ?? 0, 1),
                ],
                'target' => [
                    'calories' => $result['target_calories'] ?? 0,
                    'proteins' => round($result['target_proteins'] ?? 0, 1),
                    'fats'     => round($result['target_fats'] ?? 0, 1),
                    'carbs'    => round($result['target_carbs'] ?? 0, 1),
                ]
            ];
        } catch (PDOException $e) {
            $this->logger->error("Ошибка StatisticsRepository::getAveragesAndNorms", ['exception' => $e->getMessage()]);
            throw new \Exception("Ошибка агрегации средних значений");
        }
    }
}
