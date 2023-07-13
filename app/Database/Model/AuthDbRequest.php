<?php

namespace app\Database\Model;

use app\Database\DbRequests;

class AuthDbRequest extends DbRequests
{
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
        $sql = "SELECT `id` FROM `users` WHERE `email` = :email";
        $data = ['email' => $email];
        $userId = self::read($sql, $data, 1);
        $id = $userId['id'];
        if ($admin) {
            $req = "INSERT INTO `users_roles` (`user_id`, `roles_id`) VALUE ('$id', '1')";
        } else {
            $req = "INSERT INTO `users_roles` (`user_id`, `roles_id`) VALUE ('$id', '2')";
        }
        self::query($req);
    }

    /**
     * @param string $email
     * @return array|bool
     */
    public static function loginDbRequest(string $email): array|bool
    {
        $sql = "SELECT * FROM `users` WHERE `email` = :email";
        $data = ['email' => $email];
        return self::read($sql, $data, 1);
    }

    /**
     * @param string $userId
     * @param string $token
     * @return void
     */
    public static function logoutDbRequest(string $userId, string $token): void
    {
        $sql = "DELETE FROM `tokens` WHERE user_id = '$userId' AND token = '$token'";
        self::query($sql);
    }

    /**
     * @param string $email
     * @return array|bool
     */
    public static function resetPassDbRequest(string $email): array|bool
    {
        $sql = "SELECT `password` FROM `users` WHERE `email` = :email";
        $data = ['email' => $email];
        return self::read($sql, $data, 1);
    }

    /**
     * @param string $email
     * @param string $token
     * @return array|bool
     */
    public static function getPassDbRequest(string $email, string $token, string $temporaryPas): array|bool
    {
        $sql = "SELECT `email`, `cookies_token`, `temporary_pass`  FROM `reset_pas` WHERE `email` = :email AND `cookies_token` = :cookiesToken AND `temporary_pass` = '$temporaryPas'";
        $data = ['email' => $email, 'cookiesToken' => $token];
        return self::read($sql, $data, 1);
    }

    /**
     * @param string $passwordHash
     * @param string $userEmail
     * @return void
     */
    public static function changePasDbRequest(string $passwordHash, string $userEmail): void
    {
        $sql = "UPDATE `users` SET `password` = '$passwordHash' WHERE `email` = :email";
        $data = ['email' => $userEmail];
        self::write($sql, $data);
    }

    /**
     * @param string $userEmail
     * @param string $userCookies
     * @param string $userPas
     * @return void
     */
    public static function deletePassDbRequest(string $userEmail, string $userCookies, string $userPas): void
    {
        $sql = "DELETE FROM `reset_pas` WHERE `email` = '$userEmail' AND `cookies_token` = '$userCookies' AND `temporary_pass` = '$userPas'";
        self::query($sql);
    }
}
