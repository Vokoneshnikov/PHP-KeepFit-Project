<?php

namespace App\Repositories\Implementations;

use App\Core\Database;
use App\Core\LoggerFactory;
use App\Dtos\Requests\CreateMealRequest;
use App\Dtos\Requests\UpdateMealRequest;
use App\Dtos\Responses\MealResponse;
use App\Enums\MealType;
use App\Models\Meal;
use App\Repositories\Interfaces\IMealRepository;
use DateTimeImmutable;
use PDOException;
use Psr\Log\LoggerInterface;

class MealRepository implements IMealRepository
{
    private \PDO $pdo;

    private LoggerInterface $logger;

    public function __construct(?\PDO $pdo = null, ?LoggerInterface $logger = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->logger = $logger ?? LoggerFactory::create();
    }

    public function getById(int $id): MealResponse
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM meals
                WHERE id = :id
            ");

            $stmt->execute([
                'id' => $id,
            ]);

            $data = $stmt->fetch();

            if (!$data) {
                throw new \Exception('Прием пищи не найден');
            }

            $model = $this->mapRowToModel($data);

            return $this->mapModelToResponse($model);
        } catch (PDOException $e) {
            $msg = 'Ошибка с запросом getById';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception($msg);
        }
    }

    public function getAll(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM meals
            ");

            $stmt->execute();

            $data = $stmt->fetchAll();

            return $this->mapRowsToResponses($data);
        } catch (PDOException $e) {
            $msg = 'Ошибка с запросом getAll';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception($msg);
        }
    }

    public function save(object $request)
    {
        return match (true) {
            $request instanceof CreateMealRequest => $this->create($request),
            $request instanceof UpdateMealRequest => $this->update($request),
            default => throw new \InvalidArgumentException('Неподдерживаемый тип DTO для сохранения приема пищи'),
        };
    }

    public function delete(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM meals
                WHERE id = :id
            ");

            $stmt->execute([
                'id' => $id,
            ]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $msg = 'Ошибка с запросом delete';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception($msg);
        }
    }

    private function create(CreateMealRequest $request): MealResponse
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO meals (user_id, food_id, amount_grams, meal_type, consumed_at)
                VALUES (:user_id, :food_id, :amount_grams, :meal_type, :consumed_at)
                RETURNING id
            ");

            $stmt->execute([
                'user_id' => $request->userId,
                'food_id' => $request->foodId,
                'amount_grams' => $request->amountGrams,
                'meal_type' => $request->mealType->value,
                'consumed_at' => $request->consumedAt->format('Y-m-d'),
            ]);

            $result = $stmt->fetch();

            $model = new Meal(
                id: (int)$result['id'],
                userId: $request->userId,
                foodId: $request->foodId,
                amountGrams: $request->amountGrams,
                mealType: $request->mealType,
                consumedAt: $request->consumedAt,
            );

            return $this->mapModelToResponse($model);
        } catch (PDOException $e) {
            $msg = 'Ошибка с запросом create';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception($msg);
        }
    }

    private function update(UpdateMealRequest $request): MealResponse
    {
        $updates = [];
        $params = [
            'id' => $request->id,
        ];

        if ($request->amountGrams !== null) {
            $updates[] = 'amount_grams = :amount_grams';
            $params['amount_grams'] = $request->amountGrams;
        }

        if ($request->mealType !== null) {
            $updates[] = 'meal_type = :meal_type';
            $params['meal_type'] = $request->mealType->value;
        }

        if (empty($updates)) {
            return $this->getById($request->id);
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE meals
                SET " . implode(', ', $updates) . "
                WHERE id = :id
            ");

            $stmt->execute($params);

            return $this->getById($request->id);
        } catch (PDOException $e) {
            $msg = 'Ошибка с запросом update';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception($msg);
        }
    }

    private function mapRowToModel(array $row): Meal
    {
        return new Meal(
            id: (int)$row['id'],
            userId: (int)$row['user_id'],
            foodId: (int)$row['food_id'],
            amountGrams: (float)$row['amount_grams'],
            mealType: MealType::from($row['meal_type']),
            consumedAt: new DateTimeImmutable($row['consumed_at']),
        );
    }

    private function mapModelToResponse(Meal $meal): MealResponse
    {
        return new MealResponse(
            id: (int)$meal->id,
            userId: $meal->userId,
            foodId: $meal->foodId,
            amountGrams: $meal->amountGrams,
            mealType: $meal->mealType,
            consumedAt: $meal->consumedAt,
        );
    }

    private function mapRowsToResponses(array $rows): array
    {
        return array_map(function (array $row): MealResponse {
            $model = $this->mapRowToModel($row);

            return $this->mapModelToResponse($model);
        }, $rows);
    }
}
