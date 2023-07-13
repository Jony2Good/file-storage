<?php

namespace app\Database;

use JetBrains\PhpStorm\NoReturn;
use function config\configDb;
use function config\dbOptions;

class Database
{
    /**
     * @var object|null
     */
    private static object|null $instance = null;

    private function __construct()
    {
    }

    /**
     * @return object|null
     */
    public static function getInstance(): null|object
    {
        if (is_null(self::$instance)) {
            try {
                $config = configDb();
                $options = dbOptions();
                static::$instance = new \PDO("mysql:host=" . $config['host'] . ";port=" . $config['port'] . ";dbname=" . $config['db'] . '', $config['username'], $config['password'], $options);
            } catch (\PDOException $exception) {
                echo "DB connection error: " . $exception->getMessage();
                die();
            }
        }
        return static::$instance;
    }

    #[NoReturn] private function __clone(): void
    {
        \trigger_error("Unable to implement instruction", E_USER_ERROR);
    }

    #[NoReturn] public function __wakeup(): void
    {
        \trigger_error("Unable to implement instruction", E_USER_ERROR);
    }
}
