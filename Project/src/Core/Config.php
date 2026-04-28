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
    //записываем все параметры из .env в формате ключ-знач
    private static array $config = [];

    //загрузка конфига
    public static function load(string $path): void
    {
        try {
            $dotenv = Dotenv::createImmutable($path);
            $dotenv->load();

            $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

            self::$config = $_ENV;
        } catch (InvalidPathException $e) {
            $msg = "Файл .env не найден по пути: " . $path;
            Logger::getInstance()->error($msg);
            throw new ConfigNotFoundException($msg);
        } catch (ValidationException $e) {
            $msg = "Ошибка валидации конфига: " . $e->getMessage();
            Logger::getInstance()->error($msg);
            throw new ConfigParamMissingException($msg);
        } catch (\Exception $e) {
            $msg = "Непредвиденная ошибка при загрузке конфигурации: " . $e->getMessage();
            throw new InternalConfigException($msg);
        }
    }

    public static function get(string $key, $default = null)
    {
        return self::$config[$key] ?? $default;
    }
}
