<?php
//Базовый CRUD
namespace App\Repositories\interfaces;
interface IRepository {

    
    public function getById(int $id);
    public function getAll();

    public function save(object $obj);

    public function delete(int $id);
}