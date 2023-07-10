<?php

namespace app\Database\Model;

use app\Database\DbRequests;
use app\Services\ValidationData;

class UserDbRequest extends DbRequests
{
    public static function showUsersDbRequest(): array
    {
        $sql = "SELECT `id`, `name`,`login`, `email`, `date_created` FROM `users`";
        return self::read($sql, null, 2);
    }

    /**
     * @param string $id
     * @return array<string>
     */
    public static function getUserDbRequest(string $id): array
    {
        $sql = "SELECT u.id, u.name,u.login, r.group_name as role, u.email, u.date_created FROM `users` u LEFT JOIN `users_roles` ur ON ur.user_id = u.id LEFT JOIN `roles` r ON ur.roles_id = r.id WHERE u.id = :id";
        $data = ['id' => $id];
        return self::read($sql, $data, 2);
    }

    /**
     * @param string $id
     * @return void
     */
    public static function deleteUserDbRequest(string $id): void
    {
        $sql = 'DELETE FROM `users` WHERE id = :id';
        $data = ['id' => $id];
        self::write($sql, $data);
    }

    /**
     * @param string $name
     * @param string $login
     * @param string $email
     * @param string $id
     * @return void
     */
    public static function updateUserDbRequest(string $name, string $login, string $email, string $id): void
    {
        if (!ValidationData::checkEmailExistence($email)) {
            http_response_code(401);
            echo json_encode(array("error" => "Email {$email} is already exist. Operation denied"));
            die();
        }
        $sql = "UPDATE `users` SET `name` = :name, `login` = :login, `email` = :email WHERE `id` = :id";
        $data = ['id' => $id, 'name' => $name, 'login' => $login, 'email' => $email];
        self::write($sql, $data);
    }

    /**
     * @param string $email
     * @return array|bool
     */
    public static function searchUserDbRequest(string $email): array|bool
    {
        $sql = "SELECT `id`, `name`, `email` FROM `users` WHERE `email` = :email";
        $data = ['email' => $email];
        return self::read($sql, $data, 2);
    }

    /**
     * @param string $id
     * @return array|bool
     */
    public static function showUserDbRequest(string $id): array|bool
    {
        $sql = "SELECT u.name as user_name, u.id, u.login,u.email, f.name as directory_name, fs.name as file_name FROM `users` u LEFT JOIN folders f ON u.id = f.user_id LEFT JOIN files fs ON f.id = fs.directory_id WHERE u.id = :id";
        $data = ['id' => $id];
        return self::read($sql, $data, 1);
    }
}
