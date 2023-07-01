<?php

namespace app\Database;

use function config\configDb;
use function config\dbOptions;

class Database
{
    /**
     * @var object|null
     */
    private static ?object $instance = null;

    private function __construct()
    {
    }

    /**
     * @return object|null
     */
    public static function connect(): ?object
    {
        if (is_null(static::$instance)) {
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
}
