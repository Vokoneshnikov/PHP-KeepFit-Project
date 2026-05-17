<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\UserService;
use App\Repositories\Interfaces\IUserRepository;
use App\Dtos\Requests\CreateUserRequest;
use App\Enums\Gender;

class UserServiceTest extends TestCase
{
    //Тест 6: Исключение при неверном формате email
    public function testRegisterThrowsExceptionForInvalidEmail()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Некорректный формат email");

        $repositoryMock = $this->createMock(IUserRepository::class);
        $service = new UserService($repositoryMock);

        $request = new CreateUserRequest(
            password: 'password123',
            email: 'invalid-email-format',
            name: 'Иван',
            gender: Gender::Male,
            birthDate: new \DateTimeImmutable('2000-01-01')
        );

        $service->register($request);
    }
}
