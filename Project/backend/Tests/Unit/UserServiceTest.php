<?php

namespace backend\Tests\Unit;

use App\Dtos\Requests\CreateUserRequest;
use App\Dtos\Responses\UserResponse;
use App\Enums\Gender;
use App\Repositories\Interfaces\IUserRepository;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    /**
     * Тест: Исключение при попытке зарегистрировать дубликат email
     */
    public function testRegisterThrowsExceptionIfEmailAlreadyExists()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Пользователь с таким email уже зарегистрирован");

        $repositoryMock = $this->createMock(IUserRepository::class);

        $existingUser = new UserResponse(1, 'Иван', Gender::Male, 'test@example.com');
        $repositoryMock->method('findByEmail')->willReturn($existingUser);

        $service = new UserService($repositoryMock);

        $request = new CreateUserRequest(
            password: 'password123',
            email: 'test@example.com',
            name: 'Иван',
            gender: Gender::Male,
            birthDate: new \DateTimeImmutable('2000-01-01')
        );

        $service->register($request);
    }
    //Тест: Успешная аутентификация при верном пароле
    public function testLoginReturnsUserResponseOnValidPassword()
    {
        $repositoryMock = $this->createMock(IUserRepository::class);

        $email = 'user@fit.com';
        $password = 'secret_pass';
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $repositoryMock->method('getPasswordHashByEmail')->with($email)->willReturn($hashedPassword);

        $expectedUser = new UserResponse(5, 'Алекс', Gender::Male, $email);
        $repositoryMock->method('findByEmail')->with($email)->willReturn($expectedUser);

        $service = new UserService($repositoryMock);
        $result = $service->login($email, $password);

        $this->assertInstanceOf(UserResponse::class, $result);
        $this->assertEquals(5, $result->id);
        $this->assertEquals('Алекс', $result->name);
    }

    //Тест: Неуспешная аутентификация при неверном пароле
    public function testLoginReturnsNullOnInvalidPassword()
    {
        $repositoryMock = $this->createMock(IUserRepository::class);

        $email = 'user@fit.com';
        $hashedPassword = password_hash('correct_password', PASSWORD_BCRYPT);

        $repositoryMock->method('getPasswordHashByEmail')->with($email)->willReturn($hashedPassword);

        $service = new UserService($repositoryMock);

        $result = $service->login($email, 'wrong_password');

        $this->assertNull($result);
    }
}