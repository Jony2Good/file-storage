<?php

namespace app\Database;

class Database
{
    /**
     * @return object
     */
    public static function connect(): object
    {
        $config = require_once "../config/db.php";
        $options = require_once "../config/options.php";
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
