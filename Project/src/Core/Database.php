<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $host = Config::get('DB_HOST', 'localhost');
            $port = Config::get('DB_PORT', '5432');
            $dbname = Config::get('DB_NAME', 'myapp');
            $user = Config::get('DB_USER', 'postgres');
            $pass = Config::get('DB_PASS', '');

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

            try {
                self::$connection = new PDO(
                    $dsn,
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                $msg = "Проблема с подключением к БД {$dbname}: " . $e->getMessage();
                error_log($msg);
                throw new \Exception($msg);
            }
        }

        return self::$connection;
    }
}