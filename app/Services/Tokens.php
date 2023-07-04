<?php

namespace app\Services;

use app\Database\DbRequests;

class Tokens extends DbRequests
{
    /**
     * @param int $length
     * @return string
     */
    public static function createRandomToken(int $length = 16): string
    {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }

    /**
     * @param string $token
     * @param string $userId
     * @return void
     */
    public static function createTokenDB(string $token, string $userId): void
    {
        $sql = "SELECT `token` FROM `tokens` WHERE `user_id` = :userId";
        $data = ['userId' => $userId];
        $response = self::read($sql, $data, 3);
        if ($response > 1) {
            $req = "UPDATE `tokens` SET `token` = '$token' WHERE `user_id` = '$userId'";
        } else {
            $req = "INSERT INTO `tokens` (`token`, `user_id`) VALUES ('$token', '$userId')";
        }
        self::query($req);
    }

    /**
     * @param string $token
     * @return bool
     */
    public static function verifyUserToken(string $token): bool
    {
        if (!empty($token)) {
            $sql = "SELECT `token`, `user_id` FROM `tokens` WHERE `token` = :token";
            $data = ['token' => $token];
            $statement = self::read($sql, $data, 1);
            if ($statement) {
                return true;
            }
        }
        return false;
    }


}
