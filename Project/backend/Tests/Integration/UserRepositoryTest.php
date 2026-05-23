<?php

namespace backend\Tests\Integration;

use App\Dtos\Requests\CreateUserRequest;
use App\Dtos\Requests\UpdateUserRequest;
use App\Enums\Gender;
use App\Repositories\Implementations\UserRepository;

class UserRepositoryTest extends IntegrationTestCase
{
    public function testSaveAndGetByIdIntegration(): void
    {
        $repository = new UserRepository($this->pdo);

        $request = new CreateUserRequest(
            password: 'hashed_password_abc',
            email: 'integration@test.ru',
            name: 'Дмитрий',
            gender: Gender::Male,
            birthDate: new \DateTimeImmutable('1995-05-05')
        );

        $savedUser = $repository->save($request);

        $this->assertEquals(1, $savedUser->id);
        $this->assertEquals('Дмитрий', $savedUser->name);
        $this->assertEquals('integration@test.ru', $savedUser->email);

        $fetchedUser = $repository->getById(1);

        $this->assertEquals('Дмитрий', $fetchedUser->name);
        $this->assertEquals('integration@test.ru', $fetchedUser->email);
        $this->assertEquals(Gender::Male, $fetchedUser->gender);
    }

    public function testGetByIdThrowsExceptionIfUserNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Пользователь не найден');

        $repository = new UserRepository($this->pdo);

        $repository->getById(999);
    }

    public function testGetAllReturnsUsers(): void
    {
        $repository = new UserRepository($this->pdo);

        $this->pdo->exec("
            INSERT INTO users (name, email, password_hash, gender, birth_date)
            VALUES ('Иван', 'ivan@fit.com', '123', 'male', '2000-01-01')
        ");

        $this->pdo->exec("
            INSERT INTO users (name, email, password_hash, gender, birth_date)
            VALUES ('Анна', 'anna@fit.com', '456', 'female', '2001-01-01')
        ");

        $users = $repository->getAll();

        $this->assertCount(2, $users);
        $this->assertEquals('Иван', $users[0]->name);
        $this->assertEquals('Анна', $users[1]->name);
    }

    public function testFindByEmailReturnsUserResponse(): void
    {
        $repository = new UserRepository($this->pdo);

        $this->pdo->exec("
            INSERT INTO users (name, email, password_hash, gender, birth_date)
            VALUES ('Анна', 'anna@fit.com', '123', 'female', '2001-01-01')
        ");

        $user = $repository->findByEmail('anna@fit.com');

        $this->assertNotNull($user);
        $this->assertEquals('Анна', $user->name);
        $this->assertEquals(Gender::Female, $user->gender);
        $this->assertEquals('anna@fit.com', $user->email);
    }

    public function testFindByEmailReturnsNullIfNotFound(): void
    {
        $repository = new UserRepository($this->pdo);

        $user = $repository->findByEmail('ghost@none.com');

        $this->assertNull($user);
    }

    public function testGetPasswordHashByEmailReturnsHash(): void
    {
        $repository = new UserRepository($this->pdo);

        $this->pdo->exec("
            INSERT INTO users (name, email, password_hash, gender, birth_date)
            VALUES ('Павел', 'pavel@fit.com', 'hash_123', 'male', '1999-03-03')
        ");

        $hash = $repository->getPasswordHashByEmail('pavel@fit.com');

        $this->assertEquals('hash_123', $hash);
    }

    public function testGetPasswordHashByEmailReturnsNullIfUserNotFound(): void
    {
        $repository = new UserRepository($this->pdo);

        $hash = $repository->getPasswordHashByEmail('missing@fit.com');

        $this->assertNull($hash);
    }

    public function testGetBirthDateByIdReturnsDate(): void
    {
        $repository = new UserRepository($this->pdo);

        $this->pdo->exec("
            INSERT INTO users (name, email, password_hash, gender, birth_date)
            VALUES ('Мария', 'maria@fit.com', '123', 'female', '1998-07-15')
        ");

        $userId = (int)$this->pdo->lastInsertId();

        $birthDate = $repository->getBirthDateById($userId);

        $this->assertEquals('1998-07-15', $birthDate);
    }

    public function testGetBirthDateByIdThrowsExceptionIfUserNotFound(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Пользователь не найден');

        $repository = new UserRepository($this->pdo);

        $repository->getBirthDateById(999);
    }

    public function testSaveUpdateUserSuccess(): void
    {
        $repository = new UserRepository($this->pdo);

        $this->pdo->exec("
            INSERT INTO users (name, email, password_hash, gender, birth_date)
            VALUES ('Старое имя', 'old@fit.com', '123', 'male', '1990-01-01')
        ");

        $userId = (int)$this->pdo->lastInsertId();

        $request = new UpdateUserRequest(
            id: $userId,
            email: 'new@fit.com',
            name: 'Новое имя',
            gender: Gender::Female,
            birthDate: new \DateTimeImmutable('1995-05-05')
        );

        $updatedUser = $repository->save($request);

        $this->assertEquals($userId, $updatedUser->id);
        $this->assertEquals('Новое имя', $updatedUser->name);
        $this->assertEquals('new@fit.com', $updatedUser->email);
        $this->assertEquals(Gender::Female, $updatedUser->gender);

        $stmt = $this->pdo->prepare("
            SELECT *
            FROM users
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $userId,
        ]);

        $dbUser = $stmt->fetch();

        $this->assertEquals('Новое имя', $dbUser['name']);
        $this->assertEquals('new@fit.com', $dbUser['email']);
        $this->assertEquals('female', strtolower($dbUser['gender']));
        $this->assertEquals('1995-05-05', $dbUser['birth_date']);
    }

    public function testSaveUpdateUserWithoutChangesReturnsCurrentUser(): void
    {
        $repository = new UserRepository($this->pdo);

        $this->pdo->exec("
            INSERT INTO users (name, email, password_hash, gender, birth_date)
            VALUES ('Без изменений', 'same@fit.com', '123', 'male', '1990-01-01')
        ");

        $userId = (int)$this->pdo->lastInsertId();

        $request = new UpdateUserRequest(
            id: $userId,
            email: null,
            name: null,
            gender: null,
            birthDate: null
        );

        $user = $repository->save($request);

        $this->assertEquals('Без изменений', $user->name);
        $this->assertEquals('same@fit.com', $user->email);
    }

    public function testDeleteReturnsTrueWhenUserDeleted(): void
    {
        $repository = new UserRepository($this->pdo);

        $this->pdo->exec("
            INSERT INTO users (name, email, password_hash, gender, birth_date)
            VALUES ('Удаляемый', 'delete@fit.com', '123', 'male', '1990-01-01')
        ");

        $userId = (int)$this->pdo->lastInsertId();

        $result = $repository->delete($userId);

        $this->assertTrue($result);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM users
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $userId,
        ]);

        $this->assertEquals(0, (int)$stmt->fetchColumn());
    }

    public function testDeleteReturnsFalseWhenUserDoesNotExist(): void
    {
        $repository = new UserRepository($this->pdo);

        $result = $repository->delete(999);

        $this->assertFalse($result);
    }

    public function testSaveThrowsExceptionForUnsupportedDto(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Неподдерживаемый тип DTO');

        $repository = new UserRepository($this->pdo);

        $repository->save(new \stdClass());
    }
}