<?php

namespace app\Database;

use app\Database\Interface\Requests;

class DbRequests extends Database implements Requests
{

    public static function dbConnect(): ?object
    {
        return Database::getInstance();
    }

    /**
     * @param string $statement
     * @param array<string> $data
     * @return bool
     */
    public static function write(string $statement, array $data): bool
    {
        $db = self::dbConnect();
        $statement = $db->prepare($statement);
        return $statement->execute($data);
    }

    /**
     * @param string $statement
     * @param array|null $data
     * @param string $mode
     * @return array
     */
    public static function read(string $statement, ?array $data, string $mode): mixed
    {
        $db = self::dbConnect();
        $statement = $db->prepare($statement);
        $statement->execute($data);
        switch ($mode) {
            case 1:
                return $statement->fetch(\PDO::FETCH_ASSOC);
            case 2:
                return $statement->fetchAll(\PDO::FETCH_ASSOC);
            case 3:
                return $statement->fetchColumn();
            default:
                return false;
        }
    }

    /**
     * @param string $statement
     * @return void
     */
    public static function query(string $statement): void
    {
        self::dbConnect()->query($statement);
    }
}
