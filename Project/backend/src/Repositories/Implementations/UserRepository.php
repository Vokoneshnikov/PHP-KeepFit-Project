<?php

namespace App\Repositories\Implementations;

use App\Core\Database;
use App\Core\LoggerFactory;
use App\Dtos\Requests\CreateUserRequest;
use App\Dtos\Requests\UpdateUserRequest;
use App\Dtos\Responses\UserResponse;
use App\Enums\Gender;
use App\Models\User;
use App\Repositories\Interfaces\IUserRepository;
use DateTimeImmutable;
use PDOException;
use Psr\Log\LoggerInterface;

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
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM users
                WHERE id = :id
            ");

            $stmt->execute([
                'id' => $id,
            ]);

            $data = $stmt->fetch();

            if (!$data) {
                throw new \Exception('Пользователь не найден');
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
                FROM users
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
            $request instanceof CreateUserRequest => $this->create($request),
            $request instanceof UpdateUserRequest => $this->update($request),
            default => throw new \InvalidArgumentException('Неподдерживаемый тип DTO для сохранения пользователя'),
        };
    }

    public function delete(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM users
                WHERE id = :id
            ");

            $stmt->execute([
                'id' => $id,
            ]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $msg = 'Ошибка UserRepository::delete';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception($msg);
        }
    }

    public function findByEmail(string $email): ?UserResponse
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM users
                WHERE email = :email
            ");

            $stmt->execute([
                'email' => $email,
            ]);

            $data = $stmt->fetch();

            if (!$data) {
                return null;
            }

            $model = $this->mapRowToModel($data);

            return $this->mapModelToResponse($model);
        } catch (PDOException $e) {
            $msg = 'Ошибка UserRepository::findByEmail';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception($msg);
        }
    }

    public function getPasswordHashByEmail(string $email): ?string
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT password_hash
                FROM users
                WHERE email = :email
            ");

            $stmt->execute([
                'email' => $email,
            ]);

            $data = $stmt->fetch();

            return $data ? $data['password_hash'] : null;
        } catch (PDOException $e) {
            $msg = 'Ошибка UserRepository::getPasswordHashByEmail';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception($msg);
        }
    }

    public function getBirthDateById(int $id): string
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT birth_date
                FROM users
                WHERE id = :id
            ");

            $stmt->execute([
                'id' => $id,
            ]);

            $data = $stmt->fetch();

            if (!$data) {
                throw new \Exception('Пользователь не найден');
            }

            return $data['birth_date'];
        } catch (PDOException $e) {
            $msg = 'Ошибка UserRepository::getBirthDateById';

            $this->logger->error($msg, [
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception($msg);
        }
    }

    private function create(CreateUserRequest $request): UserResponse
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, email, password_hash, gender, birth_date)
                VALUES (:name, :email, :password_hash, :gender, :birth_date)
                RETURNING id
            ");

            $stmt->execute([
                'name' => $request->name,
                'email' => $request->email,
                'password_hash' => $request->password,
                'gender' => $request->gender->value,
                'birth_date' => $request->birthDate->format('Y-m-d'),
            ]);

            $result = $stmt->fetch();

            $model = new User(
                id: (int)$result['id'],
                email: $request->email,
                passwordHash: $request->password,
                name: $request->name,
                gender: $request->gender,
                birthDate: $request->birthDate,
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

    private function update(UpdateUserRequest $request): UserResponse
    {
        $updates = [];
        $params = [
            'id' => $request->id,
        ];

        if ($request->name !== null) {
            $updates[] = 'name = :name';
            $params['name'] = $request->name;
        }

        if ($request->gender !== null) {
            $updates[] = 'gender = :gender';
            $params['gender'] = $request->gender->value;
        }

        if ($request->birthDate !== null) {
            $updates[] = 'birth_date = :birth_date';
            $params['birth_date'] = $request->birthDate->format('Y-m-d');
        }

        if ($request->email !== null) {
            $updates[] = 'email = :email';
            $params['email'] = $request->email;
        }

        if (empty($updates)) {
            return $this->getById($request->id);
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE users
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

    private function mapRowToModel(array $row): User
    {
        return new User(
            id: (int)$row['id'],
            email: $row['email'],
            passwordHash: $row['password_hash'],
            name: $row['name'],
            gender: Gender::from(strtolower($row['gender'])),
            birthDate: new DateTimeImmutable($row['birth_date']),
            createdAt: isset($row['created_at']) && $row['created_at'] !== null
                ? new DateTimeImmutable($row['created_at'])
                : null,
        );
    }

    private function mapModelToResponse(User $user): UserResponse
    {
        return new UserResponse(
            id: (int)$user->id,
            name: $user->name,
            gender: $user->gender,
            email: $user->email,
        );
    }

    private function mapRowsToResponses(array $rows): array
    {
        return array_map(function (array $row): UserResponse {
            $model = $this->mapRowToModel($row);

            return $this->mapModelToResponse($model);
        }, $rows);
    }
}