<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Logger;
use App\Core\Config;

//загрузка конфигурации
Config::load(__DIR__ . '/../');

set_exception_handler(function ($exception) {
    Logger::getInstance()->critical("Необработанное исключение: " . $exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'type' => get_class($exception)
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