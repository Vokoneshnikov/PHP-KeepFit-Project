<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PDO;

abstract class IntegrationTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        // Создаем подключение к изолированной БД прямо в памяти
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Разворачиваем схему таблиц, которую набросали в Первый день
        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                gender VARCHAR(10) NOT NULL,
                birth_date DATE NOT NULL
            );
            CREATE TABLE foods (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                calories REAL NOT NULL,
                proteins REAL NOT NULL,
                fats REAL NOT NULL,
                carbs REAL NOT NULL,
                created_by INTEGER NULL
            );
        ");
    }
}
