<?php

namespace Tests\Integration;

use App\Repositories\Implementations\UserRepository;
use App\Dtos\Requests\CreateUserRequest;
use App\Enums\Gender;

class UserRepositoryTest extends IntegrationTestCase
{
    /**
     * Интеграционный тест 1: Успешная запись и чтение пользователя из реальной БД SQLite
     */
    public function testSaveAndGetByIdIntegration()
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
        $this->assertEquals(1, $savedUser->id); // База инкрементировала id до 1

        // Вытаскиваем обратно из базы по id
        $fetchedUser = $repository->getById(1);
        $this->assertEquals('Дмитрий', $fetchedUser->name);
        $this->assertEquals('integration@test.ru', $fetchedUser->email);
    }

    /**
     * Интеграционный тест 2: Поиск по Email возвращает корректный DTO, если запись есть
     */
    public function testFindByEmailReturnsUserResponse()
    {
        $repository = new UserRepository($this->pdo);

        // Вручную подселим пользователя в SQLite
        $this->pdo->exec("INSERT INTO users (name, email, password_hash, gender, birth_date) VALUES ('Анна', 'anna@fit.com', '123', 'Female', '2001-01-01')");

        $user = $repository->findByEmail('anna@fit.com');

        $this->assertNotNull($user);
        $this->assertEquals('Анна', $user->name);
        $this->assertEquals(Gender::Female, $user->gender);
    }

    /**
     * Интеграционный тест 3: Метод findByEmail возвращает null, если записи нет
     */
    public function testFindByEmailReturnsNullIfNotFound()
    {
        $repository = new UserRepository($this->pdo);
        $user = $repository->findByEmail('ghost@none.com');

        $this->assertNull($user); // Записи нет, репозиторий должен вернуть null без паники
    }
}
