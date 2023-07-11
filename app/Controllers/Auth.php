<?php

namespace app\Controllers;

use app\Database\Model\AuthDbRequest;
use app\HTTP\Response\ServerResponse;
use app\Services\CreateSession;
use app\Services\Interface\SessionService;
use app\Services\Tokens;
use app\Services\GeneratePass;
use app\Services\SendMailPassword;
use app\Services\ValidationData;

class Auth
{
    private string $name;

    private string $login;

    private string $email;

    private string $password;
    private string $pasConfirm;

    private string $token;

    private string $userId;

    private string $resetEmail;
    private string $tempPas;
    private string $cookies;

    /**
     * @param $data
     * @return void
     */
    private function getData($data): void
    {
        $this->name = $data["name"] ?? "";
        $this->login = $data["login"] ?? "";
        $this->email = $data["email"] ?? "";
        $this->password = $data['password'] ?? "";
        $this->pasConfirm = $data['password_confirm'] ?? "";
    }

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
        $this->tempPas = $data['tempPas'];
        $this->cookies = $data['cookies'];
        $this->resetEmail = $data['resetEmail'];
    }

    /**
     * @param array<string> $data
     * @return void
     * @throws \Exception
     */
    public function signUp(array $data): void
    {
        $this->getData($data);
        if (!ValidationData::checkNameData($this->name) || !ValidationData::checkNameData($this->login) || !ValidationData::checkEmailData($this->email)) {
            ServerResponse::createResponse(14, 400);
            die();
        }
        if (!ValidationData::checkEmailExistence($this->email)) {
            ServerResponse::createResponse(15, 400);
            die();
        }
        if ($this->password === $this->pasConfirm) {
            $passwordHash = password_hash($this->password, PASSWORD_BCRYPT);
            AuthDbRequest::signUpDB($this->name, $this->login, $this->email, $passwordHash);
            AuthDbRequest::addRolesDB($this->email);
            ServerResponse::createResponse(16);
        } else {
            ServerResponse::createResponse(14, 400);
        }
    }

    /**
     * @param array<string> $data
     * @return void
     */

    public function login(array $data): void
    {
        $this->getData($data);
        $users = AuthDbRequest::loginDbRequest($this->email);
        if (empty($users)) {
            ServerResponse::createResponseList("User with email {$this->email} is not registered", 400);
            die();
        } else {
            $userId = $users['id'];
            $usersPas = $users['password'];
            if (!password_verify($this->password, $usersPas)) {
                ServerResponse::createResponse(14, 400);
                die();
            } else {
                $this->setData();
                $this->token = Tokens::createRandomToken();
                $_SESSION['user_data'] = [
                    'sid' => $this->token,
                    'userId' => $userId,
                    'email' => $this->email
                ];
                Tokens::createTokenDB($this->token, $userId);
                ServerResponse::createResponseList([
                    "user_email" => $this->email,
                    "token" => $this->token,
                    "status" => "Password confirmed"
                ]);
            }
        }
    }

    /**
     * @return void
     */
    public function logout(): void
    {
        $this->setData();
        AuthDbRequest::logoutDbRequest($this->userId, $this->token);
        self::startSessionUser()->end();
        ServerResponse::createResponse(17);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function resetPassword(): void
    {
        $this->getData($_GET);
        if (!ValidationData::checkEmailData($this->email)) {
            ServerResponse::createResponse(14, 400);
            die();
        } else {
            $data = AuthDbRequest::resetPassDbRequest($this->email);
            if (!$data) {
                ServerResponse::createResponse(10, 404);
                die();
            } else {
                session_start();
                $_SESSION['reset_email'] = $this->email;
                $token = Tokens::createRandomToken();
                $tempPass = $_SESSION['temporary_pass'] = GeneratePass::createPassword();
                ValidationData::checkTemporaryPassword($this->email, $token, $tempPass);

                $mail = new SendMailPassword();
                $mail->sendMail($this->email);

                setcookie('reset_pas', $token, time() + 3600);

                ServerResponse::createResponseList("You temporary password is {$tempPass}");
            }
        }
    }

    /**
     * @param array<string> $post
     * @return void
     */
    public function changePassword(array $post): void
    {
        $this->setData();
        if (empty($this->cookies)) {
            ServerResponse::createResponse(3, 401);
            die();
        }
        if (!empty($this->resetEmail) && !empty($this->tempPas)) {
            $data = AuthDbRequest::getPassDbRequest($this->resetEmail, $this->cookies, $this->tempPas);
            if (isset($data['email']) && isset($data['cookies_token']) && isset($data['temporary_pass'])) {
                $newPass = $post['password'] ?? '';
                $newPassConfirm = $post['password_confirm'] ?? '';

                $userEmail = $data['email'];
                $userPas = $data['temporary_pass'];
                $userCookies = $data['cookies_token'];

                if ($newPass === $newPassConfirm) {
                    $passwordHash = password_hash($newPass, PASSWORD_BCRYPT);

                    AuthDbRequest::changePasDbRequest($passwordHash, $userEmail);
                    AuthDbRequest::deletePassDbRequest($userEmail, $userCookies, $userPas);

                    unset($_COOKIE['reset_pas']);
                    setcookie('reset_pas', "", -1, '/');
                } else {
                    ServerResponse::createResponse(18, 404);
                    die();
                }
            }
            ServerResponse::createResponse(19);
        } else {
            ServerResponse::createResponse(20, 503);
            die();
        }
    }
}
