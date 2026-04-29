<?php

namespace App\Core;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
class LoggerFactory
{
    public static function create(): LoggerInterface
    {
        $logger = new MonologLogger("logger-app");
        $logPath = __DIR__ . "/../../Logs/app.log";
        $logger->pushHandler(new StreamHandler($logPath, MonologLogger::DEBUG));
        return $logger;
    }
}
