<?php

namespace app\Database;

use app\Database\Database;
use app\Services\Roles;

class DbRequests
{
    /**
     * @param string $query
     * @param array<string> $data
     * @return bool
     */
    private static function write(string $query, array $data): bool
    {
        $db = Database::connect();
        $statement = $db->prepare($query);
        return $statement->execute($data);
    }

    /**
     * @param string $query
     * @param array<string> $data
     * @return array
     */
    private static function read(string $query, array $data):array
    {
        $db = Database::connect();
        $statement = $db->prepare($query);
        $statement->execute($data);
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $query
     * @return void
     */
    private static function query(string $query): void
    {
        Database::connect()->query($query);
    }

    /**
     * @param string $name
     * @param string $login
     * @param string $email
     * @param string $passwordHash
     * @return void
     */
    public static function signUpDB(string $name, string $login, string $email, string $passwordHash): void
    {
        $sql = "INSERT INTO `users` (`id`, `name`, `login`, `email`, `password`, `date_created`) VALUES (null, :name, :login, :email, :password, NOW())";
        $data = ['name' => $name, 'login' => $login, 'email' => $email, 'password' => $passwordHash];
        self::write($sql, $data);
    }

    /**
     * @param string $email
     * @param bool $admin
     * @return void
     */
    public static function addRolesDB(string $email, bool $admin = false): void
    {
        $query = "SELECT `id` FROM `users` WHERE `email` = :email";
        $data = ['email' => $email];
        $userId = self::read($query, $data);
        $id = $userId['id'];
        if ($admin) {
            $req = "INSERT INTO `users_roles` (`user_id`, `roles_id`) VALUE ('$id', '1')";
        } else {
            $req = "INSERT INTO `users_roles` (`user_id`, `roles_id`) VALUE ('$id', '2')";
        }
        self::query($req);
    }
}
