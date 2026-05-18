<?php

namespace App\Repositories\Implementations;

use App\Repositories\Interfaces\IUserRepository;
use App\Dtos\Requests\CreateUserRequest;
use App\Dtos\Requests\UpdateUserRequest;
use App\Dtos\Responses\UserResponse;
use App\Enums\Gender;
use App\Core\Database;
use Psr\Log\LoggerInterface;
use App\Core\LoggerFactory;
use PDOException;

class UserRepository implements IUserRepository
{
    private \PDO $pdo;
    private LoggerInterface $logger;

    public function __construct(?\PDO $pdo = null, ?LoggerInterface $logger = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->logger = $logger ?? LoggerFactory::create();
    }

    public function getById(int $id): UserResponse
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute([
            "id" => $id,
            ]);
            $data = $stmt->fetch();
            if (!$data) {
                throw new \Exception("Пользователь не найден");
            }

            return new UserResponse(
                id: $data['id'],
                name: $data['name'],
                gender: Gender::from($data['gender']),
                email: $data['email'],
            );
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
            $stmt = $this->pdo->prepare("SELECT * FROM users");
            $stmt->execute();

            $data = $stmt->fetchAll();

            return array_map(fn($row) => new UserResponse(
                id: $row['id'],
                name: $row['name'],
                gender: Gender::from($row['gender']),
                email: $row['email'],
            ), $data);
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
            $request instanceof CreateUserRequest => $this->create($request),
            $request instanceof UpdateUserRequest => $this->update($request),
            default => throw new \InvalidArgumentException("Неподдерживаемый тип DTO для сохранения")
        };
    }

    private function create(CreateUserRequest $request): UserResponse
    {
        try {
            // RETURNING id поддерживается как в PostgreSQL, так и в современных версиях SQLite
            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, email, password_hash, gender, birth_date) 
                VALUES (:name, :email, :passwordHash, :gender, :birthDate) 
                RETURNING id
            ");

            $stmt->execute([
                "name" => $request->name,
                "email" => $request->email,
                "passwordHash" => $request->password,
                "gender" => $request->gender->value,
                "birthDate" => $request->birthDate->format('Y-m-d')
            ]);

            $result = $stmt->fetch();

            return new UserResponse(
                id: $result['id'],
                name: $request->name,
                gender: $request->gender,
                email: $request->email
            );
        } catch (PDOException $e) {
            $msg = "Ошибка с запросом create";
            $this->logger->error($msg, [
                'exception' => $e->getMessage()
            ]);

            throw new \Exception($msg);
        }
    }

    private function update(UpdateUserRequest $request): UserResponse
    {
        $updates = [];
        $params = ['id' => $request->id];

        if ($request->name !== null) {
            $updates[] = "name = :name";
            $params['name'] = $request->name;
        }
        if ($request->gender !== null) {
            $updates[] = "gender = :gender";
            $params['gender'] = $request->gender->value;
        }
        if ($request->birthDate !== null) {
            $updates[] = "birth_date = :birth_date";
            $params['birth_date'] = $request->birthDate->format('Y-m-d');
        }
        if ($request->email !== null) {
            $updates[] = "email = :email";
            $params['email'] = $request->email;
        }
        if (empty($updates)) {
            return $this->getById($request->id);
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id");
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
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $msg = "Ошибка UserRepository::delete: " . $e->getMessage();
            $this->logger->error($msg, [
                'exception' => $e->getMessage()
            ]);
            throw new \Exception($msg);
        }
    }
    public function findByEmail(string $email): ?UserResponse
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id, name, gender, email FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);

            $data = $stmt->fetch();
            if (!$data) {
                return null;
            }

            return new UserResponse(
                id: $data['id'],
                name: $data['name'],
                gender: Gender::from(strtolower($data['gender'])),
                email: $data['email']
            );
        } catch (PDOException $e) {
            $msg = "Ошибка UserRepository::findByEmail: " . $e->getMessage();
            $this->logger->error($msg, [
                'exception' => $e->getMessage()
            ]);
            throw new \Exception($msg);
        }
    }

    public function getPasswordHashByEmail(string $email): ?string
    {
        try {
            $stmt = $this->pdo->prepare("SELECT password_hash FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);

            $data = $stmt->fetch();
            return $data ? $data['password_hash'] : null;
        } catch (PDOException $e) {
            $msg = "Ошибка UserRepository::getPasswordHashByEmail: " . $e->getMessage();
            $this->logger->error($msg, [
                'exception' => $e->getMessage()
            ]);

            throw new \Exception($msg);
        }
    }
}
