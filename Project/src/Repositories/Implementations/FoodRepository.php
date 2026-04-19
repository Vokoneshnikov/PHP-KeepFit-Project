<?php
namespace App\Repositories\Implementations;

use App\Repositories\interfaces\IMealRepository;

class FoodRepository implements IFoodRepository {
    public function getById(int $id);
    public function getAll();

    public function save();

    public function delete();

}