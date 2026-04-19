<?php
namespace App\Repositories\Implementations;

use App\Repositories\interfaces\IMealRepository;

class MealRepository implements IMealRepository {
    public function getById(int $id);
    public function getAll();

    public function save();

    public function delete();

}