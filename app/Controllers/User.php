<?php

namespace app\Controllers;

use app\Database\Database;
use app\Services\CheckTokens;
use app\Services\Roles;

class User
{
    public function showUserList(): void
    {
        session_start();
        $db = Database::connect();
        $token = $_SESSION['user_data']['sid'] ?? '';
        $userID = $_SESSION['user_data']['userId'] ?? '';
        if (Roles::checkRoles($db, $userID)) {
            if (CheckTokens::verifyUserToken($db, $token)) {
                $statement = $db->query("SELECT `id`, `name`,`login`, `email`, `date_created` FROM `users`");
                $response = $statement->fetchAll(\PDO::FETCH_ASSOC);
                http_response_code(200);
                echo json_encode($response);
            }
        }
    }

    /**
     * @param string $id
     * @return void
     */
    public function getUser(string $id): void
    {
        session_start();
        $db = Database::connect();
        $userID = $_SESSION['user_data']['userId'] ?? '';
        if (Roles::checkRoles($db, $userID)) {
            $sql = "SELECT u.id, u.name,u.login, r.group_name as role, u.email, u.date_created FROM `users` u LEFT JOIN `users_roles` ur ON ur.user_id = u.id LEFT JOIN `roles` r ON ur.roles_id = r.id WHERE u.id = :id";
            $statement = $db->prepare($sql);
            $statement->execute(['id' => $id]);
            $user = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if (!$user) {
                http_response_code(400);
                echo json_encode(array("error" => "User not found"));
            } else {
                http_response_code(200);
                echo json_encode($user);
            }
        }
    }

    /**
     * @param string $id
     * @return void
     */
    public function deleteUser(string $id): void
    {
        session_start();
        $db = Database::connect();
        $userID = $_SESSION['user_data']['userId'] ?? '';
        if (Roles::checkRoles($db, $userID)) {
            $sql = "SELECT `id` FROM `users` WHERE `id` = :id";
            $statement = $db->prepare($sql);
            $statement->execute(['id' => $id]);
            $user = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if (!$user) {
                http_response_code(400);
                echo json_encode(array("error" => "User not found"));
                die();
            } else {
                $sql = 'DELETE FROM `users` WHERE id = :id';
                $statement = $db->prepare($sql);
                $statement->execute(['id' => $id]);
                http_response_code(200);
                echo json_encode(array("message" => "User with id: {$id} was deleted"));
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function update(): void
    {
        session_start();
        $db = Database::connect();
        $userID = $_SESSION['user_data']['userId'] ?? '';
        $json = file_get_contents('php://input');
        if (!$json) {
            http_response_code(401);
            echo json_encode(array("error" => "Failed with entry information"));
            die();
        }
        $obj = json_decode($json, true);
        if (!isset($obj)) {
            throw new \Exception('Bad JSON');
        }
        $id = $obj['id'] ?? '';
        $name = $obj['name'] ?? '';
        $login = $obj['login'] ?? '';
        $email = $obj['email'] ?? '';
        if (Roles::checkRoles($db, $userID)) {
            $sql = "SELECT `id` FROM `users` WHERE `id` = :id";
            $statement = $db->prepare($sql);
            $statement->execute(['id' => $id]);
            $user = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if (!$user) {
                http_response_code(400);
                echo json_encode(array("error" => "User not found"));
                die();
            } else {
                $sql = "UPDATE `users` SET `name` = :name, `login` = :login, `email` = :email WHERE `id` = :id";
                $statement = $db->prepare($sql);
                $statement->execute([
                    'id' => $id,
                    'name' => $name,
                    'login' => $login,
                    'email' => $email,
                ]);
                http_response_code(200);
                echo json_encode(array("message" => "User with id: {$id} was updated"));
            }
        }
    }
    /**
     * @param string $data
     * @return void
     * @throws \Exception
     */
    public function userSearch(string $data): void
    {
        session_start();
        $db = Database::connect();
        $userId = $_SESSION['user_data']['userId'] ?? '';
        if (Roles::checkRoles($db, $userId)) {
            $sql = "SELECT u.name as user_name, u.id, u.login,u.email, f.name as directory_name, fs.name as file_name FROM `users` u LEFT JOIN folders f ON u.id = f.user_id LEFT JOIN files fs ON f.id = fs.directory_id WHERE u.email = :email";
            $statement = $db->prepare($sql);
            $statement->execute(['email' => $data]);
            $user = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if (!$user) {
                http_response_code(400);
                echo json_encode(array("message" => "User not found"));
                die();
            } else {
                http_response_code(200);
                echo json_encode(array("user" => $user));
            }
        }
    }
}
