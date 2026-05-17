<?php

namespace App\Repositories\Implementations;

use App\Repositories\Interfaces\IMealRepository;
use App\Dtos\Requests\CreateMealRequest;
use App\Dtos\Requests\UpdateMealRequest;
use App\Dtos\Responses\MealResponse;
use App\Enums\MealType;
use App\Core\Database;
use Psr\Log\LoggerInterface;
use App\Core\LoggerFactory;
use PDOException;

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
            $stmt = $this->pdo->prepare("SELECT * FROM meals WHERE id = :id");
            $stmt->execute([
            "id" => $id,
            ]);
            $data = $stmt->fetch();
            if (!$data) {
                throw new \Exception("Прием пищи не найден");
            }

            $mealDto = new MealResponse(
                id: $data['id'],
                userId: $data['user_id'],
                foodId: $data['food_id'],
                amountGrams: $data['amount_grams'],
                mealType: MealType::from($data['meal_type']),
                consumedAt: new \DateTimeImmutable($data['consumed_at'])
            );

            return $mealDto;
        } catch (PDOException $e) {
            $msg = "Ошибка с запросом getById";
            $this->logger->error($msg, [
                'exception' => $e->getMessage()
            ]);

            throw new \Exception($msg);
        }
    }
    public function getAll(): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM meals");
            $stmt->execute();

            $data = $stmt->fetchAll();

            $meals = array_map(fn($row) =>  new MealResponse(
                id: $row['id'],
                userId: $row['user_id'],
                foodId: $row['food_id'],
                amountGrams: $row['amount_grams'],
                mealType: MealType::from($row['meal_type']),
                consumedAt: new \DateTimeImmutable($row['consumed_at']),
            ), $data);

            return $meals;
        } catch (PDOException $e) {
            $msg = "Ошибка с запросом getAll";
            $this->logger->error($msg, [
                'exception' => $e->getMessage()
            ]);

            throw new \Exception($msg);
        }
    }

    public function save(object $request)
    {
        return match (true) {
            $request instanceof CreateMealRequest => $this->create($request),

            $request instanceof UpdateMealRequest => $this->update($request),

            default => throw new \InvalidArgumentException("...")
        };
    }

    private function create(CreateMealRequest $request): MealResponse
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO meals (user_id, food_id, amount_grams, meal_type, consumed_at) VALUES (:user_id, :food_id, :amount_grams, :meal_type, :consumed_at) RETURNING id");
            $stmt->execute([
                "user_id" => $request->userId,
                "food_id" => $request->foodId,
                "amount_grams" => $request->amountGrams,
                "meal_type" => $request->mealType->value,
                "consumed_at" => $request->consumedAt->format('Y-m-d')
            ]);

            $result = $stmt->fetch();

            return new MealResponse(
                id: $result['id'],
                userId: $request->userId,
                foodId: $request->foodId,
                amountGrams: $request->amountGrams,
                mealType: $request->mealType,
                consumedAt: $request->consumedAt,
            );
        } catch (PDOException $e) {
            $msg = "Ошибка с запросом create";
            $this->logger->error($msg, [
                'exception' => $e->getMessage()
            ]);

            throw new \Exception($msg);
        }
    }

    private function update(UpdateMealRequest $request): MealResponse
    {
        $updates = [];
        $params = ['id' => $request->id];

        if ($request->amountGrams != null) {
            $updates[] = "amount_grams = :amount_grams";
            $params['amount_grams'] = $request->amountGrams;
        }
        if ($request->mealType != null) {
            $updates[] = "meal_type = :meal_type";
            $params['meal_type'] = $request->mealType->value;
        }

        if (empty($updates)) {
            return $this->getById($request->id);
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE meals SET " . implode(', ', $updates) . " WHERE id = :id");
            $stmt->execute($params);

            return $this->getById($request->id);
        } catch (PDOException $e) {
            $msg = "Ошибка с запросом update";
            $this->logger->error($msg, [
                'exception' => $e->getMessage()
            ]);

            throw new \Exception($msg);
        }
    }

    public function delete(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM meals WHERE id = :id");
            $stmt->execute([
                "id" => $id,
            ]);
            $deletedRows = $stmt->rowCount();

            return !(($deletedRows === 0));
        } catch (PDOException $e) {
            $msg = "Ошибка с запросом delete";
            $this->logger->error($msg, [
                'exception' => $e->getMessage()
            ]);

            throw new \Exception($msg);
        }
    }
}
