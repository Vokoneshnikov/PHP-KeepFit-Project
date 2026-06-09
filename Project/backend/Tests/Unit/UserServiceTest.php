<?php

namespace backend\Tests\Unit;

use App\Dtos\Requests\CreateUserRequest;
use App\Dtos\Requests\UpdateUserRequest;
use App\Dtos\Responses\UserResponse;
use App\Enums\Gender;
use App\Repositories\Interfaces\IUserRepository;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    public function testRegisterThrowsExceptionIfEmailAlreadyExists(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Пользователь с таким email уже зарегистрирован');

        $repositoryMock = $this->createMock(IUserRepository::class);

        $existingUser = new UserResponse(1, 'Иван', Gender::Male, 'test@example.com');

        $repositoryMock
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($existingUser);

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

    public function testRegisterThrowsExceptionIfEmailInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Некорректный формат email');

        $repositoryMock = $this->createMock(IUserRepository::class);

        $repositoryMock
            ->expects($this->never())
            ->method('save');

        $service = new UserService($repositoryMock);

        $request = new CreateUserRequest(
            password: 'password123',
            email: 'bad-email',
            name: 'Иван',
            gender: Gender::Male,
            birthDate: new \DateTimeImmutable('2000-01-01')
        );

        $service->register($request);
    }

    public function testRegisterSuccessHashesPasswordAndSavesUser(): void
    {
        $repositoryMock = $this->createMock(IUserRepository::class);

        $repositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with('new@fit.com')
            ->willReturn(null);

        $repositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (CreateUserRequest $request): bool {
                return $request->email === 'new@fit.com'
                    && $request->name === 'Новый пользователь'
                    && $request->gender === Gender::Male
                    && password_verify('secret123', $request->password);
            }))
            ->willReturn(new UserResponse(
                id: 10,
                name: 'Новый пользователь',
                gender: Gender::Male,
                email: 'new@fit.com'
            ));

        $service = new UserService($repositoryMock);

        $request = new CreateUserRequest(
            password: 'secret123',
            email: 'new@fit.com',
            name: 'Новый пользователь',
            gender: Gender::Male,
            birthDate: new \DateTimeImmutable('2000-01-01')
        );

        $result = $service->register($request);

        $this->assertEquals(10, $result->id);
        $this->assertEquals('Новый пользователь', $result->name);
        $this->assertEquals('new@fit.com', $result->email);
    }

    public function testLoginReturnsUserResponseOnValidPassword(): void
    {
        $repositoryMock = $this->createMock(IUserRepository::class);

        $email = 'user@fit.com';
        $password = 'secret_pass';
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $repositoryMock
            ->method('getPasswordHashByEmail')
            ->with($email)
            ->willReturn($hashedPassword);

        $expectedUser = new UserResponse(5, 'Алекс', Gender::Male, $email);

        $repositoryMock
            ->method('findByEmail')
            ->with($email)
            ->willReturn($expectedUser);

        $service = new UserService($repositoryMock);

        $result = $service->login($email, $password);

        $this->assertInstanceOf(UserResponse::class, $result);
        $this->assertEquals(5, $result->id);
        $this->assertEquals('Алекс', $result->name);
    }

    public function testLoginReturnsNullOnInvalidPassword(): void
    {
        $repositoryMock = $this->createMock(IUserRepository::class);

        $email = 'user@fit.com';
        $hashedPassword = password_hash('correct_password', PASSWORD_BCRYPT);

        $repositoryMock
            ->method('getPasswordHashByEmail')
            ->with($email)
            ->willReturn($hashedPassword);

        $service = new UserService($repositoryMock);

        $result = $service->login($email, 'wrong_password');

        $this->assertNull($result);
    }

    public function testLoginReturnsNullWhenUserHashNotFound(): void
    {
        $repositoryMock = $this->createMock(IUserRepository::class);

        $repositoryMock
            ->method('getPasswordHashByEmail')
            ->with('missing@fit.com')
            ->willReturn(null);

        $repositoryMock
            ->expects($this->never())
            ->method('findByEmail');

        $service = new UserService($repositoryMock);

        $result = $service->login('missing@fit.com', 'password');

        $this->assertNull($result);
    }

    public function testGetProfileReturnsRepositoryResult(): void
    {
        $repositoryMock = $this->createMock(IUserRepository::class);

        $expectedUser = new UserResponse(
            id: 1,
            name: 'Иван',
            gender: Gender::Male,
            email: 'ivan@fit.com'
        );

        $repositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($expectedUser);

        $service = new UserService($repositoryMock);

        $this->assertSame($expectedUser, $service->getProfile(1));
    }

    public function testUpdateProfileThrowsExceptionIfEmailInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Некорректный формат email');

        $repositoryMock = $this->createMock(IUserRepository::class);

        $repositoryMock
            ->expects($this->never())
            ->method('save');

        $service = new UserService($repositoryMock);

        $request = new UpdateUserRequest(
            id: 1,
            email: 'bad-email',
            name: 'Иван',
            gender: Gender::Male,
            birthDate: null
        );

        $service->updateProfile($request);
    }

    public function testUpdateProfileThrowsExceptionIfEmailBelongsToAnotherUser(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Этот email уже занят другим пользователем');

        $repositoryMock = $this->createMock(IUserRepository::class);

        $repositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with('used@fit.com')
            ->willReturn(new UserResponse(
                id: 2,
                name: 'Другой пользователь',
                gender: Gender::Female,
                email: 'used@fit.com'
            ));

        $repositoryMock
            ->expects($this->never())
            ->method('save');

        $service = new UserService($repositoryMock);

        $request = new UpdateUserRequest(
            id: 1,
            email: 'used@fit.com',
            name: 'Иван',
            gender: Gender::Male,
            birthDate: null
        );

        $service->updateProfile($request);
    }

    public function testUpdateProfileSuccessWhenEmailIsFree(): void
    {
        $repositoryMock = $this->createMock(IUserRepository::class);

        $request = new UpdateUserRequest(
            id: 1,
            email: 'free@fit.com',
            name: 'Иван Новый',
            gender: Gender::Male,
            birthDate: new \DateTimeImmutable('2000-01-01')
        );

        $expectedUser = new UserResponse(
            id: 1,
            name: 'Иван Новый',
            gender: Gender::Male,
            email: 'free@fit.com'
        );

        $repositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with('free@fit.com')
            ->willReturn(null);

        $repositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($request)
            ->willReturn($expectedUser);

        $service = new UserService($repositoryMock);

        $this->assertSame($expectedUser, $service->updateProfile($request));
    }

    public function testUpdateProfileSuccessWhenEmailBelongsToCurrentUser(): void
    {
        $repositoryMock = $this->createMock(IUserRepository::class);

        $request = new UpdateUserRequest(
            id: 1,
            email: 'same@fit.com',
            name: 'Иван',
            gender: Gender::Male,
            birthDate: null
        );

        $expectedUser = new UserResponse(
            id: 1,
            name: 'Иван',
            gender: Gender::Male,
            email: 'same@fit.com'
        );

        $repositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with('same@fit.com')
            ->willReturn($expectedUser);

        $repositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($request)
            ->willReturn($expectedUser);

        $service = new UserService($repositoryMock);

        $this->assertSame($expectedUser, $service->updateProfile($request));
    }

    public function testGetUserBirthDateReturnsRepositoryResult(): void
    {
        $repositoryMock = $this->createMock(IUserRepository::class);

        $repositoryMock
            ->expects($this->once())
            ->method('getBirthDateById')
            ->with(1)
            ->willReturn('2000-01-01');

        $service = new UserService($repositoryMock);

        $this->assertEquals('2000-01-01', $service->getUserBirthDate(1));
    }

    public function testGenerateTokensReturnsAccessAndRefreshTokens(): void
    {
        $_ENV['JWT_SECRET'] = 'test_secret';

        $repositoryMock = $this->createMock(IUserRepository::class);
        $service = new UserService($repositoryMock);

        $user = new UserResponse(
            id: 7,
            name: 'Тест',
            gender: Gender::Male,
            email: 'test@fit.com'
        );

        $tokens = $service->generateTokens($user);

        $this->assertArrayHasKey('access_token', $tokens);
        $this->assertArrayHasKey('refresh_token', $tokens);

        $this->assertNotEmpty($tokens['access_token']);
        $this->assertNotEmpty($tokens['refresh_token']);

        $this->assertCount(3, explode('.', $tokens['access_token']));
        $this->assertEquals(64, strlen($tokens['refresh_token']));
    }
}