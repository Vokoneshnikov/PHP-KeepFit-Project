<?php

namespace App\Repositories\Implementations;

use App\Core\Database;
use App\Core\LoggerFactory;
use App\Dtos\Requests\CreateFoodRequest;
use App\Dtos\Requests\UpdateFoodRequest;
use App\Dtos\Responses\FoodResponse;
use App\Models\Food;
use App\Repositories\Interfaces\IFoodRepository;
use PDOException;
use Psr\Log\LoggerInterface;

class FoodRepository implements IFoodRepository
{
    private \PDO $pdo;
    private LoggerInterface $logger;

    public function __construct(?\PDO $pdo = null, ?LoggerInterface $logger = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->logger = $logger ?? LoggerFactory::create();
    }
    public function search(string $query): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM foods
                WHERE name LIKE :query
                LIMIT 50
            ");

            $stmt->execute([
                'query' => '%' . $query . '%',
            ]);

            $data = $stmt->fetchAll();

            return $this->mapRowsToResponses($data);
        } catch (PDOException $e) {
            $msg = 'Ошибка с запросом search';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception($msg);
        }
    }

    public function getRecentByUserId(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT f.*
                FROM foods f
                JOIN (
                    SELECT food_id, MAX(id) AS last_meal_id
                    FROM meals
                    WHERE user_id = :user_id
                    GROUP BY food_id
                ) recent ON recent.food_id = f.id
                ORDER BY recent.last_meal_id DESC
                LIMIT 15
            ");

            $stmt->execute([
                'user_id' => $userId,
            ]);

            $data = $stmt->fetchAll();

            return $this->mapRowsToResponses($data);
        } catch (PDOException $e) {
            $msg = 'Ошибка с запросом getRecentByUserId';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception($msg);
        }
    }

    public function getCustomByUserId(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM foods
                WHERE created_by = :user_id
                ORDER BY id DESC
            ");

            $stmt->execute([
                'user_id' => $userId,
            ]);

            $data = $stmt->fetchAll();

            return $this->mapRowsToResponses($data);
        } catch (PDOException $e) {
            $msg = 'Ошибка с запросом getCustomByUserId';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception($msg);
        }
    }

    public function getById(int $id): FoodResponse
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM foods
                WHERE id = :id
            ");

            $stmt->execute([
                'id' => $id,
            ]);
            $data = $stmt->fetch();
            if (!$data) {
                throw new \Exception('Продукт не найден');
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
                FROM foods
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
            $request instanceof CreateFoodRequest => $this->create($request),
            $request instanceof UpdateFoodRequest => $this->update($request),
            default => throw new \InvalidArgumentException('Неподдерживаемый тип DTO для сохранения продукта'),
        };
    }

    public function delete(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM foods
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

    private function create(CreateFoodRequest $request): FoodResponse
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO foods (name, calories, proteins, fats, carbs, created_by)
                VALUES (:name, :calories, :proteins, :fats, :carbs, :created_by)
                RETURNING id
            ");

            $stmt->execute([
                'name' => $request->name,
                'calories' => $request->calories,
                'proteins' => $request->proteins,
                'fats' => $request->fats,
                'carbs' => $request->carbs,
                'created_by' => $request->createdBy,
            ]);

            $result = $stmt->fetch();

            $model = new Food(
                id: (int)$result['id'],
                name: $request->name,
                calories: $request->calories,
                proteins: $request->proteins,
                fats: $request->fats,
                carbs: $request->carbs,
                createdBy: $request->createdBy,
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
    private function update(UpdateFoodRequest $request): FoodResponse
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE foods
                SET
                    name = :name,
                    calories = :calories,
                    proteins = :proteins,
                    fats = :fats,
                    carbs = :carbs
                WHERE id = :id
            ");

            $stmt->execute([
                'id' => $request->id,
                'name' => $request->name,
                'calories' => $request->calories,
                'proteins' => $request->proteins,
                'fats' => $request->fats,
                'carbs' => $request->carbs,
            ]);

            return $this->getById($request->id);
        } catch (PDOException $e) {
            $msg = "Ошибка с запросом update";
            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception($msg);
        }
    }

    private function mapRowToModel(array $row): Food
    {
        return new Food(
            id: (int)$row['id'],
            name: $row['name'],
            calories: (float)$row['calories'],
            proteins: (float)$row['proteins'],
            fats: (float)$row['fats'],
            carbs: (float)$row['carbs'],
            createdBy: array_key_exists('created_by', $row) && $row['created_by'] !== null
                ? (int)$row['created_by']
                : null,
        );
    }

    private function mapModelToResponse(Food $food): FoodResponse
    {
        return new FoodResponse(
            id: (int)$food->id,
            name: $food->name,
            calories: $food->calories,
            proteins: $food->proteins,
            fats: $food->fats,
            carbs: $food->carbs,
            createdBy: $food->createdBy,
        );
    }

    private function mapRowsToResponses(array $rows): array
    {
        return array_map(function (array $row): FoodResponse {
            $model = $this->mapRowToModel($row);

            return $this->mapModelToResponse($model);
        }, $rows);
    }
}