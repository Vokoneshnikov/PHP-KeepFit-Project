<?php
namespace App\Repositories\Implementations;

use App\Dtos\Requests\CreateUserRequest;
use App\Dtos\Requests\UpdateUserRequest;
use App\Repositories\interfaces\IUserRepository;
use App\Dtos\Responses\UserResponse;
use App\Enums\Gender;
use App\Core\Database;
use App\Core\Logger;
use PDOException;
class UserRepository implements IUserRepository {
    private \PDO $pdo;

    public function __construct(?\PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function getById(int $id) : UserResponse {
        try {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([
            "id" => $id,
        ]);
        $data = $stmt->fetch();
        if (!$data) {
            throw new \Exception("Пользователь не найден"); 
        }

        $userDto = new UserResponse (
            id: $data['id'],
            name: $data['name'],
            gender: Gender::from($data['gender']),
            email: $data['email'],
        );

        return $userDto;
        }
        catch (PDOException $e) {
            $msg = "Ошибка с запросом getById";
            Logger::getInstance()->error($msg);
            throw new \Exception($msg);

        }
    }
    public function getAll() : array {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users");
            $stmt->execute();

            $data = $stmt->fetchAll();

            $users = array_map(fn($row) =>  new UserResponse (
                id: $row['id'],
                name: $row['name'],
                gender: Gender::from($row['gender']),
                email: $row['email'],
            ), $data);

            return $users;
        }
        catch (PDOException $e){
            $msg = "Ошибка с запросом getAll";
            Logger::getInstance()->error($msg);
            throw new \Exception($msg);
        }

    }

    public function save(object $request){
        return match(true) {
            $request instanceof CreateUserRequest => $this->create($request),

            $request instanceof UpdateUserRequest => $this->update($request),

            default => throw new \InvalidArgumentException("...")
        };
    }

    private function create(CreateUserRequest $request) : UserResponse{
        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password_hash, gender, birth_date) VALUES (:name, :email, :passwordHash, :gender, :birthDate) RETURNING id");
            $stmt->execute([
                "name" => $request->name,
                "email" => $request->email,
                "passwordHash" => $request->passwordHash,
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
        }
        catch (PDOException $e) {
            $msg = "Ошибка с запросом create";
            Logger::getInstance()->error($msg);
            throw new \Exception($msg);
        }
    }

    private function update(UpdateUserRequest $request) : UserResponse {
        $updates = [];
        $params = ['id' => $request->id];

        if ($request->name != null) {
            $updates[] = "name = :name";
            $params['name'] = $request->name;
        }
        if ($request->gender != null) {
            $updates[] = "gender = :gender";
            $params['gender'] = $request->gender->value;
        }
        if ($request->birthDate != null) {
            $updates[] = "birth_date = :birth_date";
            $params['birth_date'] = $request->birthDate->format('Y-m-d');
        }
        if (empty($updates)) {
            return $this->getById($request->id);
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id");
            $stmt->execute($params);

            $user = $this->getById($request->id);

            if (!$user) {
                throw new \Exception("Пользователь не найден после обновления.");
            }
            return $user;
        }
        catch (PDOException $e) {
            $msg = "Ошибка с запросом update";
            Logger::getInstance()->error($msg);
            throw new \Exception($msg);
        }
    }

    public function delete(int $id) : bool{
        try {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([
                "id" => $id,
            ]);
            $deletedRows = $stmt->rowCount();

            return ($deletedRows === 0) ? false : true;
        }
        catch (PDOException $e) {
            $msg = "Ошибка с запросом delete";
            Logger::getInstance()->error($msg);
            throw new \Exception($msg);
        }

    }

}