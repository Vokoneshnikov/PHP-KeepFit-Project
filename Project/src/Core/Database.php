<?php
namespace App\Core;
use PDO;
class Database {
    private static ?PDO $connection = null;
    public static function getConnection() : PDO{
        if (self::$connection === null) {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $dbname = $_ENV['DB_NAME'] ?? 'myapp';
            $user = $_ENV['DB_USER'] ?? 'postgres';
            $pass = $_ENV['DB_PASS'] ?? '';
            $dsn = "pgsql:host={$host};dbname={$dbname};options='--client_encoding=UTF8'";

            try {
                self::$connection = new PDO (
                    $dsn,
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ] 
                );
            }
            catch (\PDOException $e){
                $msg = "Проблема с подключением к БД " . $dbname;
            Logger::getInstance()->error($msg);
            throw new \Exception($msg);
            }
        }
        return self::$connection;
    }
}