<?php

namespace App\Core;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Dotenv\Exception\ValidationException;
use App\Exceptions\Config\ConfigNotFoundException;
use App\Exceptions\Config\ConfigParamMissingException;
use App\Exceptions\Config\InternalConfigException;

class Config
{
    private static array $config = [];

    public static function load(string $path): void
    {
        try {
            $dotenv = Dotenv::createImmutable($path);
            $dotenv->load();

            $dotenv
                ->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'])
                ->notEmpty();

            self::$config = $_ENV;
        } catch (InvalidPathException $e) {
            $msg = "Файл .env не найден по пути: " . $path;
            error_log($msg);
            throw new ConfigNotFoundException($msg);
        } catch (ValidationException $e) {
            $msg = "Ошибка валидации конфига: " . $e->getMessage();
            error_log($msg);
            throw new ConfigParamMissingException($msg);
        } catch (\Exception $e) {
            $msg = "Непредвиденная ошибка при загрузке конфигурации: " . $e->getMessage();
            error_log($msg);
            throw new InternalConfigException($msg);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$config[$key] ?? $default;
    }
}