<?php

namespace app\Controllers;

use app\Database\Model\UserDbRequest;
use app\HTTP\Response\ServerResponse;
use app\Services\CreateSession;
use app\Services\Interface\SessionService;
use app\Services\Tokens;
use app\Services\ValidationData;

class User
{
    private string $token;
    private string $userId;
    private string $id;
    private string $name;
    private string $login;
    private string $email;

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

    /**
     * @return void
     * @throws \Exception
     */
    private function getJson(): void
    {
        $json = file_get_contents('php://input');
        if (!$json) {
            ServerResponse::createResponse(1, 400);
            die();
        }
        $obj = json_decode($json, true);
        if (!isset($obj)) {
            throw new \Exception('Bad JSON');
        }
        $this->id = $obj['id'] ?? '';
        $this->name = $obj['name'] ?? '';
        $this->login = $obj['login'] ?? '';
        $this->email = $obj['email'] ?? '';
    }

    public function showUserList(): void
    {
        $this->setData();
        if (ValidationData::checkRoles($this->userId) && Tokens::verifyUserToken($this->token)) {
            $response = UserDbRequest::showUsersDbRequest();
            ServerResponse::createResponseList($response);
        } else {
            ServerResponse::createResponse(3, 401);
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
            $response = UserDbRequest::getUserDbRequest($id);
            if (!$response) {
                ServerResponse::createResponse(10, 400);
                die();
            } else {
                ServerResponse::createResponseList($response);
            }
        } else {
            ServerResponse::createResponse(3, 401);
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
                ServerResponse::createResponse(10, 400);
                die();
            } else {
                UserDbRequest::deleteUserDbRequest($id);
                ServerResponse::createResponse(11);
            }
        } else {
            ServerResponse::createResponse(3, 401);
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
            $this->getJson();
            if (!ValidationData::checkUser($this->id)) {
                ServerResponse::createResponse(10, 400);
                die();
            } else {
                UserDbRequest::updateUserDbRequest($this->name, $this->login, $this->email, $this->id);
                ServerResponse::createResponse(12);
            }
        } else {
            ServerResponse::createResponse(3, 401);
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
            $response = UserDbRequest::searchUserDbRequest($email);
            if (!$response) {
                ServerResponse::createResponse(10, 400);
                die();
            } else {
                ServerResponse::createResponseList($response);
            }
        } else {
            ServerResponse::createResponse(3, 401);
        }
    }

    public function showUsers(): void
    {
        $this->setData();
        if (Tokens::verifyUserToken($this->token)) {
            $response = UserDbRequest::showUsersDbRequest();
            ServerResponse::createResponseList($response);
        } else {
            ServerResponse::createResponse(3, 401);
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
            $response = UserDbRequest::showUserDbRequest($id);
            if (!$response) {
                ServerResponse::createResponse(10, 400);
            } else {
                ServerResponse::createResponseList($response);
            }
        } else {
            ServerResponse::createResponse(3, 401);
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
            $this->getJSON();
            if ($this->id !== $this->userId) {
                ServerResponse::createResponse(13, 401);
                die();
            }
            UserDbRequest::updateUserDbRequest($this->name, $this->login, $this->email, $this->id);
            ServerResponse::createResponse(12);
        } else {
            ServerResponse::createResponse(3, 401);
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
                ServerResponse::createResponse(13, 401);
                die();
            }
            UserDbRequest::deleteUserDbRequest($id);
            ServerResponse::createResponse(11);
        } else {
            ServerResponse::createResponse(3, 401);
        }
    }
}
