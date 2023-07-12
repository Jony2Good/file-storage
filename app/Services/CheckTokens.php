<?php

namespace app\Services;

class CheckTokens
{
    /**
     * @param \PDO $db
     * @param string $token
     * @param string $userId
     * @return void
     */
    public static function createTokenDB(\PDO $db, string $token, string $userId): void
    {
        $sql = "SELECT `token` FROM `tokens` WHERE `user_id` = :userId";
        $statement = $db->prepare($sql);
        $statement->execute(['userId' => $userId]);
        $response = $statement->fetchColumn();
        if ($response > 1) {
            $db->query("UPDATE `tokens` SET `token` = '$token' WHERE `user_id` = '$userId'");
        } else {
            $db->query("INSERT INTO `tokens` (`token`, `user_id`) VALUES ('$token', '$userId')");
        }
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

    /**
     * @param \PDO $db
     * @param string $email
     * @param string $token
     * @param string $tempPass
     * @return void
     */
    public static function checkTemporaryPassword(\PDO $db, string $email, string $token, string $tempPass): void
    {
        $sql = "SELECT `id` FROM `reset_pas` WHERE `email` = :email";
        $statement = $db->prepare($sql);
        $statement->execute(['email' => $email]);
        $response = $statement->fetchColumn();
        if ($response > 1) {
            $db->query("UPDATE `reset_pas` SET `cookies_token` = '$token', `temporary_pass` = '$tempPass' WHERE `email` = '$email'");
        } else {
            $db->query("INSERT INTO `reset_pas` (`id`, `email`, `cookies_token`,`temporary_pass`) VALUES (null, '$email', '$token', '$tempPass')");
        }
    }
}
