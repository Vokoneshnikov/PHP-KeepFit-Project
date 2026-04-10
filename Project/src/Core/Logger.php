<?php

namespace App\Core;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;

class Logger {
    private static $logger = null;

    public static function getInstance() : MonologLogger{
        if (self::$logger == null) {
            $logger = new MonologLogger("logger-app");
            $logger->pushHandler(new StreamHandler(__DIR__ . "/../..logs/app.log", MonologLogger::DEBUG));
            self::$logger = $logger;
        }
        return self::$logger;
    }
}
