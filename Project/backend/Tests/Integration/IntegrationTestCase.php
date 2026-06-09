<?php

namespace backend\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

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
                proteins REAL DEFAULT 0,
                fats REAL DEFAULT 0,
                carbs REAL DEFAULT 0,
                created_by INTEGER NULL,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
            );
            CREATE TABLE meals (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                food_id INTEGER NOT NULL,
                amount_grams INTEGER NOT NULL CHECK (amount_grams > 0),
                meal_type VARCHAR(50) NOT NULL,
                consumed_at DATE NOT NULL DEFAULT CURRENT_DATE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE RESTRICT
            );
        ");
    }
}
