<?php

namespace App\Services;

use App\Dtos\Requests\UpdateUserRequest;
use App\Repositories\Interfaces\IUserRepository;
use App\Dtos\Requests\CreateUserRequest;
use App\Dtos\Responses\UserResponse;

class UserService
{
    public function __construct(
        private readonly IUserRepository $userRepository
    ) {}
    public function getProfile(int $id): UserResponse
    {
        return $this->userRepository->getById($id);
    }

    public function register(CreateUserRequest $request): UserResponse
    {
        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Некорректный формат email");
        }

        if ($this->userRepository->findByEmail($request->email) !== null) {
            throw new \InvalidArgumentException("Пользователь с таким email уже зарегистрирован");
        }

        $hashedPassword = password_hash($request->password, PASSWORD_BCRYPT);

        $requestWithHash = new CreateUserRequest(
            password: $hashedPassword,
            email: $request->email,
            name: $request->name,
            gender: $request->gender,
            birthDate: $request->birthDate
        );

        return $this->userRepository->save($requestWithHash);
    }

    public function login(string $email, string $password): ?UserResponse
    {
        $hash = $this->userRepository->getPasswordHashByEmail($email);

        if (!$hash || !password_verify($password, $hash)) {
            return null;
        }

        return $this->userRepository->findByEmail($email);
    }
    public function updateProfile(UpdateUserRequest $request): UserResponse
    {
        if ($request->email !== null && !filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Некорректный формат email");
        }

        if ($request->email !== null) {
            $existingUser = $this->userRepository->findByEmail($request->email);
            if ($existingUser !== null && $existingUser->id !== $request->id) {
                throw new \InvalidArgumentException("Этот email уже занят другим пользователем");
            }
        }
        return $this->userRepository->save($request);
    }
    public function generateTokens(UserResponse $user): array
    {
        $payload = [
            'sub' => $user->id,
            'email' => $user->email,
            'role' => 'User',
            'exp' => time() + 3600 // Access токен на 1 час
        ];

        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $secret = $_ENV['JWT_SECRET'] ?? 'super_secret_key_123';
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        $accessToken = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        $refreshToken = bin2hex(random_bytes(32));

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken
        ];
    }

    private function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
