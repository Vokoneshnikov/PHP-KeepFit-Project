<?php

namespace App\Controllers;

use App\Core\Route;
use App\Services\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;

class ProfileController
{
    public function __construct(
        // private UserService $userService,
    ) {
    }

    #[Route('/profile', ['GET'])]
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], "Контроллер: Profile, Метод: index");
    }

    #[Route('/profile/edit', ['GET'])]
    public function editProfileInfo(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], "Контроллер: Profile, Метод: editProfileInfo");
    }

    #[Route('/profile/edit', ['POST'])]
    public function updateProfileInfo(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], "Контроллер: Profile, Метод: updateProfileInfo");
    }

    #[Route('/profile/recalculate', ['GET'])]
    public function editRecalculationInfo(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], "Контроллер: Profile, Метод: editRecalculationInfo");
    }

    #[Route('/profile/recalculate', ['POST'])]
    public function recalculate(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], "Контроллер: Profile, Метод: recalculate");
    }
}
