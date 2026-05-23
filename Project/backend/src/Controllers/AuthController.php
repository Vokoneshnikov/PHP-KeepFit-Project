<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Services\UserService;
use App\Dtos\Requests\CreateUserRequest;
use App\Enums\Gender;
use App\Core\Route;

class AuthController extends BaseController
{
    public function __construct(private readonly UserService $userService)
    {
    }

    #[Route('/api/register', ['POST'])]
    public function register(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->getJsonBody($request);

        $requiredFields = ['email', 'password', 'name', 'gender', 'birthDate'];
        foreach ($requiredFields as $field) {
            if (empty($body[$field])) {
                return $this->error("Отсутствует или пустое обязательное поле: {$field}");
            }
        }

        $gender = Gender::tryFrom($body['gender']);
        if (!$gender) {
            return $this->error("Передано недопустимое значение для поля gender. Допустимые: male, female");
        }

        try {
            $birthDate = new \DateTimeImmutable($body['birthDate']);
        } catch (\Exception $e) {
            return $this->error("Некорректный формат даты рождения. Используйте YYYY-MM-DD", 400);
        }

        try {
            $dto = new CreateUserRequest(
                password: $body['password'],
                email: $body['email'],
                name: $body['name'],
                gender: $gender,
                birthDate: $birthDate
            );

            $userResponse = $this->userService->register($dto);
            return $this->json($userResponse, 201);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->error("Ошибка регистрации: " . $e->getMessage(), 400);
        }
    }

    #[Route('/api/login', ['POST'])]
    public function login(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->getJsonBody($request);

        if (empty($body['email']) || empty($body['password'])) {
            return $this->error("Необходимо указать email и password");
        }

        $user = $this->userService->login($body['email'], $body['password']);
        if (!$user) {
            return $this->error("Неверный email или пароль", 400);
        }

        $tokens = $this->userService->generateTokens($user);
        return $this->json($tokens, 200);
    }
}
