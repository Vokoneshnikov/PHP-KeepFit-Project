<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Core\Route;
class ProfileController {
    public function __construct(
        // private UserService $userService,
    ) {}

    #[Route('/profile', ['GET'])]
    public function index() {
        echo "Контроллер: Profile, Метод: index";
    }
    #[Route('/profile/edit',  ['GET'])]
    public function editProfileInfo() {
        echo "Контроллер: Profile, Метод: editProfileInfo";
    }
    #[Route('/profile/edit',  ['POST'])]
    public function updateProfileInfo() {
        echo "Контроллер: Profile, Метод: updateProfileInfo";
    }
    #[Route('/profile/recalculate',  ['GET'])]
    public function editRecalculationInfo() {
        echo "Контроллер: Profile, Метод: editRecalculationInfo";
    }
    #[Route('/profile/recalculate',  ['POST'])]
    public function recalculate() {
        echo "Контроллер: Profile, Метод: recalculate";
    }
}

// ProfileController:
// GET /profile - получение страницы профиля
// GET /profile/edit - получение формы на редактирования информации о профиле
// POST /profile/edit - отправка формы
// GET /profile/recalculate - получение формы на пересчет нормы КБЖУ
// POST /profile/recalculate - отправка формы