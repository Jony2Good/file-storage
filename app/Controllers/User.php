<?php

namespace app\Controllers;

use app\Database\Model\UserDbRequest;
use app\Services\CreateSession;
use app\Services\Interface\SessionService;
use app\Services\Tokens;
use app\Services\ValidationData;

class User
{
    private string $token;
    private string $userId;

    /**
     * @return  SessionService
     */
    private static function startSessionUser(): SessionService
    {
        return new CreateSession();
    }

    private function setData(): void
    {
        $data = self::startSessionUser()->start();
        $this->token = $data['token'];
        $this->userId = $data['id'];
    }


    public function showUserList(): void
    {
        $this->setData();
        if (ValidationData::checkRoles($this->userId) && Tokens::verifyUserToken($this->token)) {
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
        $this->setData();
        if (ValidationData::checkRoles($this->userId) && Tokens::verifyUserToken($this->token)) {
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
        $this->setData();
        if (ValidationData::checkRoles($this->userId) && Tokens::verifyUserToken($this->token)) {
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
        $this->setData();
        if (ValidationData::checkRoles($this->userId) && Tokens::verifyUserToken($this->token)) {
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
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
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
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
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
        $this->setData();
        if ((Tokens::verifyUserToken($this->token)) && ($id === $this->userId)) {
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
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
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
            if ((int)$id != $this->userId) {
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
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            if ($id !== $this->userId) {
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
