<?php

namespace app\Controllers;

use app\Database\Model\AuthDbRequest;
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
            http_response_code(400);
            echo json_encode(array("error" => "Entering personal information incorrect"));
            die();
        }
        if (!ValidationData::checkEmailExistence($this->email)) {
            http_response_code(400);
            echo json_encode(array("error" => "Email {$this->email} is already exist"));
            die();
        }
        if ($this->password === $this->pasConfirm) {
            $passwordHash = password_hash($this->password, PASSWORD_BCRYPT);

            AuthDbRequest::signUpDB($this->name, $this->login, $this->email, $passwordHash);
            AuthDbRequest::addRolesDB($this->email);

            http_response_code(200);
            echo json_encode(array("message" => "User created"));
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Incorrectly entered email or password"));
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
            http_response_code(401);
            echo json_encode(array("error" => "User with email {$this->email} is not registered"));
            die();
        } else {
            $userId = $users['id'];
            $usersPas = $users['password'];
            if (!password_verify($this->password, $usersPas)) {
                http_response_code(400);
                echo json_encode(array("error" => "Entering personal information incorrect"));
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
                http_response_code(200);
                echo json_encode(array(
                    "user_email" => $this->email,
                    "token" => $this->token,
                    "status" => "Password confirmed"
                ));
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
        echo json_encode(array("message" => "You are logout from app"));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function resetPassword(): void
    {
        $this->getData($_GET);
        if (!ValidationData::checkEmailData($this->email)) {
            http_response_code(400);
            echo json_encode(array("error" => "Entering personal information incorrect"));
            die();
        } else {
            $data = AuthDbRequest::resetPassDbRequest($this->email);
            if (!$data) {
                http_response_code(404);
                echo json_encode(array("message" => "User not found"));
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

                echo json_encode(array("message" => "You temporary password is {$tempPass}"));
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
            http_response_code(401);
            echo json_encode(array("message" => "You do not have access to this page"));
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
                    http_response_code(401);
                    echo json_encode(array("message" => "Password mismatch"));
                    die();
                }
            }
            http_response_code(200);
            echo json_encode(array("message" => "Password changed successfully"));
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Error in data processing on the server"));
            die();
        }
    }
}
