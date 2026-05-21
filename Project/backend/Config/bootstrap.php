<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\DIContainer;
use App\Core\LoggerFactory;
use Psr\Log\LoggerInterface;

//загрузка конфигурации
Config::load(__DIR__  . '/../');
$logger = LoggerFactory::create();

$container = new DIContainer();

// Получаем PDO через Database, а не создаём вручную в bootstrap.php
$pdo = Database::getConnection();

// Регистрируем зависимости в контейнере
$container->set(\PDO::class, $pdo);
$container->set(LoggerInterface::class, $logger);

set_exception_handler(function ($exception) use ($logger) {
    $logger->critical("Необработанное исключение: " . $exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ]);

    http_response_code(500);

    $isDebug = $_ENV['APP_DEBUG'] ?? 'false';

    if ($isDebug === 'true') {
        echo "<h1>Debug Mode</h1>";
        echo "<p><b>Error:</b> " . $exception->getMessage() . "</p>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
    } else {
        $viewPath = __DIR__ . '/../views/errors/500.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "<h1>500 Internal Server Error</h1><p>Упс! Что-то пошло не так.</p>";
        }
    }
    exit;
});