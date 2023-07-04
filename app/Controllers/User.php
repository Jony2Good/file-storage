<?php

namespace app\Controllers;

use app\Database\Model\UserDbRequest;
use app\Services\Tokens;
use app\Services\ValidationData;

class User
{
    /**
     * @return array|string
     */
    private static function startSessionUser(): array|string
    {
        session_start();
        return $_SESSION['user_data'] ?? "";
    }

    public function showUserList(): void
    {
        $data = self::startSessionUser();
        $token = $data['sid'] ?? '';
        $userId = $data['userId'] ?? '';
        if (ValidationData::checkRoles($userId) && Tokens::verifyUserToken($token)) {
            $response = UserDbRequest::showUsersDbRequest();
            http_response_code(200);
            echo json_encode($response);
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    /**
     * @param string $id
     * @return void
     */
    public function getUser(string $id): void
    {
        $data = self::startSessionUser();
        $userId = $data['userId'] ?? '';
        $token = $data['sid'] ?? '';
        if (ValidationData::checkRoles($userId) && Tokens::verifyUserToken($token)) {
            $user = UserDbRequest::getUserDbRequest($id);
            if (!$user) {
                http_response_code(400);
                echo json_encode(array("error" => "User not found"));
            } else {
                http_response_code(200);
                echo json_encode($user);
            }
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    /**
     * @param string $id
     * @return void
     */
    public function deleteUser(string $id): void
    {
        $data = self::startSessionUser();
        $userId = $data['userId'] ?? '';
        $token = $data['sid'] ?? '';
        if (ValidationData::checkRoles($userId) && Tokens::verifyUserToken($token)) {
            if (!ValidationData::checkUser($id)) {
                http_response_code(400);
                echo json_encode(array("error" => "User not found"));
                die();
            } else {
                UserDbRequest::deleteUserDbRequest($id);
                http_response_code(200);
                echo json_encode(array("message" => "User with id: {$id} was deleted"));
            }
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function updateUser(): void
    {
        $data = self::startSessionUser();
        $userId = $data['userId'] ?? '';
        $token = $data['sid'] ?? '';
        if (ValidationData::checkRoles($userId) && Tokens::verifyUserToken($token)) {
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
            if (!ValidationData::checkUser($id)) {
                http_response_code(400);
                echo json_encode(array("error" => "User not found"));
                die();
            } else {
                UserDbRequest::updateUserDbRequest($name, $login, $email, $id);
                http_response_code(200);
                echo json_encode(array("message" => "User with id: {$id} was updated"));
            }
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    /**
     * @param string $email
     * @return void
     */
    public function searchUser(string $email): void
    {
        $data = self::startSessionUser();
        $token = $data['sid'] ?? '';
        if (Tokens::verifyUserToken($token)) {
            $user = UserDbRequest::searchUserDbRequest($email);
            if (!$user) {
                http_response_code(400);
                echo json_encode(array("message" => "User not found"));
                die();
            } else {
                http_response_code(200);
                echo json_encode(array("user" => $user));
            }
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    public function showUsers(): void
    {
        $data = self::startSessionUser();
        $token = $data['sid'] ?? '';
        if (Tokens::verifyUserToken($token)) {
            $response = UserDbRequest::showUsersDbRequest();
            http_response_code(200);
            echo json_encode($response);
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    /**
     * @param string $id
     * @return void
     */
    public function showOneUser(string $id): void
    {
        $data = self::startSessionUser();
        $userId = $data['userId'] ?? '';
        $token = $data['sid'] ?? '';
        if ((Tokens::verifyUserToken($token)) && ((int)$id === $userId)) {
            $user = UserDbRequest::showUserDbRequest($id);
            if (!$user) {
                http_response_code(400);
                echo json_encode(array("error" => "User not found"));
            } else {
                http_response_code(200);
                echo json_encode($user);
            }
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function changeUserData(): void
    {
        $data = self::startSessionUser();
        $token = $data['sid'] ?? '';
        $userId = $data['userId'] ?? '';
        if (Tokens::verifyUserToken($token)) {
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
            if ((int)$id !== $userId) {
                http_response_code(401);
                echo json_encode(array("error" => "Cancel operation. Data error"));
                die();
            }
            UserDbRequest::updateUserDbRequest($name, $login, $email, $id);
            http_response_code(200);
            echo json_encode(array("message" => "User updated"));
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }
    }

    /**
     * @param string $id
     * @return void
     */
    public function deleteOneUser(string $id): void
    {
        $data = self::startSessionUser();
        $userId = $data['userId'] ?? '';
        $token = $data['sid'] ?? '';
        if (Tokens::verifyUserToken($token)) {
            if ((int)$id !== $userId) {
                http_response_code(401);
                echo json_encode(array("error" => "Cancel operation. Data error"));
                die();
            }
            UserDbRequest::deleteUserDbRequest($id);
            http_response_code(200);
            echo json_encode(array("message" => "User deleted"));
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Page access denied"));
        }

    }
}
