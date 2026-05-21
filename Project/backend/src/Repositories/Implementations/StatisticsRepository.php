<?php

namespace App\Repositories\Implementations;

use App\Core\Database;
use App\Core\LoggerFactory;
use App\Enums\ActivityLevel;
use App\Enums\FitnessGoal;
use App\Models\DailyNorm;
use App\Models\UserParameter;
use App\Repositories\Interfaces\IStatisticsRepository;
use DateTimeImmutable;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

class StatisticsRepository implements IStatisticsRepository
{
    private readonly PDO $pdo;

    private readonly LoggerInterface $logger;

    public function __construct(?PDO $pdo = null, ?LoggerInterface $logger = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->logger = $logger ?? LoggerFactory::create();
    }

    public function saveDailyNorm(
        int $userId,
        int $calories,
        float $proteins,
        float $fats,
        float $carbs
    ): void {
        try {
            $model = new DailyNorm(
                id: null,
                userId: $userId,
                calories: $calories,
                proteins: $proteins,
                fats: $fats,
                carbs: $carbs,
            );

            $stmt = $this->pdo->prepare("
                INSERT INTO daily_norms (user_id, calories, proteins, fats, carbs)
                VALUES (:user_id, :calories, :proteins, :fats, :carbs)
            ");

            $stmt->execute([
                'user_id' => $model->userId,
                'calories' => $model->calories,
                'proteins' => $model->proteins,
                'fats' => $model->fats,
                'carbs' => $model->carbs,
            ]);
        } catch (PDOException $e) {
            $msg = 'Ошибка StatisticsRepository::saveDailyNorm';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception('Не удалось сохранить дневные нормы в БД');
        }
    }

    public function saveUserParameters(
        int $userId,
        float $weight,
        int $height,
        string $activityLevel,
        string $goal
    ): void {
        try {
            $model = new UserParameter(
                id: null,
                userId: $userId,
                weight: $weight,
                height: $height,
                activityLevel: ActivityLevel::from($activityLevel),
                goal: FitnessGoal::from($goal),
            );

            $stmt = $this->pdo->prepare("
                INSERT INTO user_parameters (user_id, weight, height, activity_factor, goal)
                VALUES (:user_id, :weight, :height, :activity_factor, :goal)
            ");

            $stmt->execute([
                'user_id' => $model->userId,
                'weight' => $model->weight,
                'height' => $model->height,
                'activity_factor' => $model->activityLevel->value,
                'goal' => $model->goal->value,
            ]);
        } catch (PDOException $e) {
            $msg = 'Ошибка StatisticsRepository::saveUserParameters';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception('Не удалось сохранить параметры пользователя в БД: ' . $e->getMessage());
        }
    }

    public function getWeeklyProgress(int $userId): array
    {
        try {
            $query = "
                WITH RECURSIVE days AS (
                    SELECT DATE_TRUNC('week', CURRENT_DATE)::date AS day
                    UNION ALL
                    SELECT (day + 1)::date
                    FROM days
                    WHERE day < DATE_TRUNC('week', CURRENT_DATE)::date + 6
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
            $stmt->execute([
                'user_id' => $userId,
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $msg = 'Ошибка StatisticsRepository::getWeeklyProgress';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception('Ошибка при генерации недельной выборки');
        }
    }

    public function getMonthlyProgress(int $userId): array
    {
        try {
            $query = "
                WITH RECURSIVE days AS (
                    SELECT DATE_TRUNC('month', CURRENT_DATE)::date AS day
                    UNION ALL
                    SELECT (day + 1)::date
                    FROM days
                    WHERE day < (DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month' - INTERVAL '1 day')::date
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
            $stmt->execute([
                'user_id' => $userId,
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $msg = 'Ошибка StatisticsRepository::getMonthlyProgress';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception('Ошибка при генерации месячной выборки');
        }
    }

    public function getAveragesAndNorms(int $userId, string $period = 'week'): array
    {
        try {
            $interval = $period === 'month'
                ? "DATE_TRUNC('month', CURRENT_DATE)"
                : "DATE_TRUNC('week', CURRENT_DATE)";

            $query = "
                SELECT
                    AVG(daily_totals.total_calories)::integer AS avg_calories,
                    AVG(daily_totals.total_proteins)::float AS avg_proteins,
                    AVG(daily_totals.total_fats)::float AS avg_fats,
                    AVG(daily_totals.total_carbs)::float AS avg_carbs,

                    (
                        SELECT calories
                        FROM daily_norms
                        WHERE user_id = :user_id
                        ORDER BY created_at DESC
                        LIMIT 1
                    ) AS target_calories,

                    (
                        SELECT proteins
                        FROM daily_norms
                        WHERE user_id = :user_id
                        ORDER BY created_at DESC
                        LIMIT 1
                    ) AS target_proteins,

                    (
                        SELECT fats
                        FROM daily_norms
                        WHERE user_id = :user_id
                        ORDER BY created_at DESC
                        LIMIT 1
                    ) AS target_fats,

                    (
                        SELECT carbs
                        FROM daily_norms
                        WHERE user_id = :user_id
                        ORDER BY created_at DESC
                        LIMIT 1
                    ) AS target_carbs
                FROM (
                    SELECT
                        m.consumed_at,
                        SUM((m.amount_grams / 100.0) * f.calories) AS total_calories,
                        SUM((m.amount_grams / 100.0) * f.proteins) AS total_proteins,
                        SUM((m.amount_grams / 100.0) * f.fats) AS total_fats,
                        SUM((m.amount_grams / 100.0) * f.carbs) AS total_carbs
                    FROM meals m
                    JOIN foods f ON m.food_id = f.id
                    WHERE m.user_id = :user_id
                      AND m.consumed_at >= {$interval}
                    GROUP BY m.consumed_at
                ) AS daily_totals
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                'user_id' => $userId,
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            return [
                'avg' => [
                    'calories' => (int)($result['avg_calories'] ?? 0),
                    'proteins' => round((float)($result['avg_proteins'] ?? 0), 1),
                    'fats' => round((float)($result['avg_fats'] ?? 0), 1),
                    'carbs' => round((float)($result['avg_carbs'] ?? 0), 1),
                ],
                'target' => [
                    'calories' => (int)($result['target_calories'] ?? 0),
                    'proteins' => round((float)($result['target_proteins'] ?? 0), 1),
                    'fats' => round((float)($result['target_fats'] ?? 0), 1),
                    'carbs' => round((float)($result['target_carbs'] ?? 0), 1),
                ],
            ];
        } catch (PDOException $e) {
            $msg = 'Ошибка StatisticsRepository::getAveragesAndNorms';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception('Ошибка агрегации средних значений');
        }
    }

    public function getLatestUserParameters(int $userId): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    id,
                    user_id,
                    weight,
                    height,
                    activity_factor::text AS activity_factor,
                    goal::text AS goal,
                    measured_at
                FROM user_parameters
                WHERE user_id = :user_id
                ORDER BY measured_at DESC
                LIMIT 1
            ");

            $stmt->execute([
                'user_id' => $userId,
            ]);

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return null;
            }

            $model = $this->mapUserParameterRowToModel($data);

            return $this->mapUserParameterModelToArray($model);
        } catch (PDOException $e) {
            $msg = 'Ошибка StatisticsRepository::getLatestUserParameters';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception('Не удалось получить последние параметры пользователя');
        }
    }

    public function getLatestDailyNorm(int $userId): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    id,
                    user_id,
                    calories,
                    proteins,
                    fats,
                    carbs,
                    created_at
                FROM daily_norms
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT 1
            ");

            $stmt->execute([
                'user_id' => $userId,
            ]);

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return null;
            }

            $model = $this->mapDailyNormRowToModel($data);

            return $this->mapDailyNormModelToArray($model);
        } catch (PDOException $e) {
            $msg = 'Ошибка StatisticsRepository::getLatestDailyNorm';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception('Не удалось получить последнюю норму КБЖУ');
        }
    }

    private function mapUserParameterRowToModel(array $row): UserParameter
    {
        return new UserParameter(
            id: (int)$row['id'],
            userId: (int)$row['user_id'],
            weight: (float)$row['weight'],
            height: (int)$row['height'],
            activityLevel: ActivityLevel::from($row['activity_factor']),
            goal: FitnessGoal::from($row['goal']),
            measuredAt: isset($row['measured_at']) && $row['measured_at'] !== null
                ? new DateTimeImmutable($row['measured_at'])
                : null,
        );
    }

    private function mapUserParameterModelToArray(UserParameter $parameter): array
    {
        return [
            'weight' => round($parameter->weight, 2),
            'height' => $parameter->height,
            'activityLevel' => $parameter->activityLevel->value,
            'goal' => $parameter->goal->value,
            'measuredAt' => $parameter->measuredAt?->format('Y-m-d H:i:s'),
        ];
    }

    private function mapDailyNormRowToModel(array $row): DailyNorm
    {
        return new DailyNorm(
            id: (int)$row['id'],
            userId: (int)$row['user_id'],
            calories: (int)$row['calories'],
            proteins: (float)$row['proteins'],
            fats: (float)$row['fats'],
            carbs: (float)$row['carbs'],
            createdAt: isset($row['created_at']) && $row['created_at'] !== null
                ? new DateTimeImmutable($row['created_at'])
                : null,
        );
    }

    private function mapDailyNormModelToArray(DailyNorm $norm): array
    {
        return [
            'calories' => $norm->calories,
            'proteins' => round($norm->proteins, 1),
            'fats' => round($norm->fats, 1),
            'carbs' => round($norm->carbs, 1),
            'createdAt' => $norm->createdAt?->format('Y-m-d H:i:s'),
        ];
    }
}
