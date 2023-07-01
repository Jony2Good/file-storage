<?php

namespace app\Database;

use function config\configDb;
use function config\dbOptions;

class Database
{
    /**
     * @return object
     */
    public static function connect(): object
    {
        $config = configDb();
        $options = dbOptions();
        $db = null;
        if (@$config['enable']) {
            try {
                $db = new \PDO("mysql:host=" . $config['host'] . ";port=" . $config['port'] . ";dbname=" . $config['db'] . '', $config['username'], $config['password'], $options);
            } catch (\PDOException $exception) {
                echo "DB connection error: " . $exception->getMessage();
                die();
            }
        }
        return $db;
    }
}
