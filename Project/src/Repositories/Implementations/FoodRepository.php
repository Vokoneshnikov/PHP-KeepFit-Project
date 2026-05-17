<?php

namespace App\Repositories\Implementations;

use App\Repositories\Interfaces\IFoodRepository;
use App\Dtos\Requests\CreateFoodRequest;
use App\Dtos\Requests\UpdateFoodRequest;
use App\Dtos\Responses\FoodResponse;
use App\Core\Database;
use Psr\Log\LoggerInterface;
use App\Core\LoggerFactory;
use PDOException;

class FoodRepository implements IFoodRepository
{
    private \PDO $pdo;
    private LoggerInterface $logger;

    public function __construct(?\PDO $pdo = null, ?LoggerInterface $logger = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->logger = $logger ?? LoggerFactory::create();
    }

    public function getById(int $id): FoodResponse
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM foods WHERE id = :id");
            $stmt->execute([
            "id" => $id,
            ]);
            $data = $stmt->fetch();
            if (!$data) {
                throw new \Exception("Продукт не найден");
            }

            $foodDto = new FoodResponse(
                id: $data['id'],
                name: $data['name'],
                calories: $data['calories'],
                proteins: $data['proteins'],
                fats: $data['fats'],
                carbs: $data['carbs'],
                createdBy: $data['created_by'],
            );

            return $foodDto;
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
            $stmt = $this->pdo->prepare("SELECT * FROM foods");
            $stmt->execute();

            $data = $stmt->fetchAll();

            $foods = array_map(fn($row) =>  new FoodResponse(
                id: $row['id'],
                name: $row['name'],
                calories: $row['calories'],
                proteins: $row['proteins'],
                fats: $row['fats'],
                carbs: $row['carbs'],
                createdBy: $row['created_by'],
            ), $data);

            return $foods;
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
            $request instanceof CreateFoodRequest => $this->create($request),

            $request instanceof UpdateFoodRequest => $this->update($request),

            default => throw new \InvalidArgumentException("...")
        };
    }

    private function create(CreateFoodRequest $request): FoodResponse
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO foods (name, calories, proteins, fats, carbs, created_by) VALUES (:name, :calories, :proteins, :fats, :carbs, :created_by) RETURNING id");
            $stmt->execute([
                'name' => $request->name,
                'calories' => $request->calories,
                'proteins' => $request->proteins,
                'fats' => $request->fats,
                'carbs' => $request->carbs,
                'created_by' => $request->createdBy,
            ]);

            $result = $stmt->fetch();

            return new FoodResponse(
                id: $result['id'],
                name: $request->name,
                calories: $request->calories,
                proteins: $request->proteins,
                fats: $request->fats,
                carbs: $request->carbs,
                createdBy: $request->createdBy,
            );
        } catch (PDOException $e) {
            $msg = "Ошибка с запросом create";
            $this->logger->error($msg, [
                'exception' => $e->getMessage()
            ]);

            throw new \Exception($msg);
        }
    }
    private function update(UpdateFoodRequest $request): FoodResponse
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE foods SET name = :name, calories = :calories, proteins = :proteins, fats = :fats, carbs = :carbs  WHERE id = :id"
            );
            $stmt->execute([
                'id' => $request->id,
                'name' => $request->name,
                'proteins' => $request->proteins,
                'fats' => $request->fats,
                'carbs' => $request->carbs,
                'calories' => $request->calories,
            ]);

            $food = $this->getById($request->id);

            if (!$food) {
                throw new \Exception("Прием пищи не найден.");
            }
            return $food;
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
            $stmt = $this->pdo->prepare("DELETE FROM foods WHERE id = :id");
            $stmt->execute([
                "id" => $id,
            ]);
            $deletedRows = $stmt->rowCount();

            return ($deletedRows === 0) ? false : true;
        } catch (PDOException $e) {
            $msg = "Ошибка с запросом delete";
            $this->logger->error($msg, [
                'exception' => $e->getMessage()
            ]);

            throw new \Exception($msg);
        }
    }
}
