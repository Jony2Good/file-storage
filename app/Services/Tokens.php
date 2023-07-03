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
     * @param \PDO $db
     * @param string $token
     * @return false|mixed
     */
    public static function verifyUserToken(\PDO $db, string $token)
    {
        if (!empty($token)) {
            $sql = "SELECT `token`, `user_id` FROM `tokens` WHERE `token` = :token";
            $statement = $db->prepare($sql);
            $statement->bindParam(':token', $token);
            $statement->execute();
            $tokenDB = $statement->fetch(\PDO::FETCH_ASSOC);
            return $tokenDB['token'];
        } else {
            http_response_code(401);
            echo json_encode(array("message" => "You are not authorized to visit this page"));
            return false;
        }
    }


}
