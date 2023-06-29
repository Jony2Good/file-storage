<?php

namespace app\Services;

class Roles
{
    /**
     * @param \PDO $db
     * @param string $email
     * @param bool $admin
     * @return void
     * @throws \Exception
     */
    public static function addRoles(\PDO $db, string $email, bool $admin = false): void
    {
        $statement = $db->query("SELECT `id` FROM `users` WHERE `email` = '$email'");
        if(!$statement) {
            throw new \Exception('Bad adding roles');
        }
        $userId = $statement->fetch(\PDO::FETCH_ASSOC);
        $id = $userId['id'];
        if ($admin) {
            $db->query("INSERT INTO `users_roles` (`user_id`, `roles_id`) VALUE ('$id', '1') ");
        } else {
            $db->query("INSERT INTO `users_roles` (`user_id`, `roles_id`) VALUE ('$id', '2')");
        }
    }

    /**
     * @param \PDO $db
     * @param string $userId
     * @return bool
     * @throws \Exception
     */
    public static function checkRoles(\PDO $db, string $userId): bool
    {
        if (empty($userId)) {
            return false;
        }
        $statement = $db->query("SELECT u.id, r.group_name FROM `users` u INNER JOIN users_roles ur ON u.id = ur.user_id INNER JOIN roles r ON ur.roles_id = r.id HAVING u.id = '$userId' AND r.group_name = 'admin'");
        if(!$statement) {
            throw new \Exception('Bad checking roles');
        }
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(401);
            echo json_encode(array("message" => "Access to this page is denied. Contact the administrator"));
            return false;
        } else {
            http_response_code(200);
            return true;
        }
    }
}
