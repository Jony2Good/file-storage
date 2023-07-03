<?php

namespace app\Controllers;

use app\Database\Database;
use app\Database\Model\AuthDbRequests;
use app\Services\Tokens;
use app\Services\GeneratePass;
use app\Services\SendMailPassword;
use app\Services\ValidationData;

class Auth
{
    /**
     * @param array<string> $data
     * @return void
     * @throws \Exception
     */
    public function signUp(array $data): void
    {
        if (empty($data)) {
            http_response_code(400);
            echo json_encode(array("error" => "Bad request"));
            die();
        }
        $name = $data["name"] ?? "";
        $login = $data["login"] ?? "";
        $email = $data["email"] ?? "";
        $password = $data['password'] ?? "";
        $pasConfirm = $data['password_confirm'] ?? "";

        if (!ValidationData::checkNameData($name) || !ValidationData::checkNameData($login) || !ValidationData::checkEmailData($email)) {
            http_response_code(400);
            echo json_encode(array("error" => "Entering personal information incorrect"));
            die();
        }
        if (!ValidationData::checkEmailExistence($email)) {
            http_response_code(400);
            echo json_encode(array("error" => "Email {$email} is already exist"));
            die();
        }
        if ($password === $pasConfirm) {
            $passwordHash = password_hash($data["password"], PASSWORD_BCRYPT);

            AuthDbRequests::signUpDB($name, $login, $email, $passwordHash);
            AuthDbRequests::addRolesDB($email);

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
        $email = $data['email'] ?? "";
        $password = $data['password'] ?? "";
        $users = AuthDbRequests::loginDbRequest($email);
        if (empty($users)) {
            http_response_code(401);
            echo json_encode(array("error" => "User with email {$email} is not registered"));
            die();
        } else {
            $userId = $users['id'];
            $usersPas = $users['password'];
            if (!password_verify($password, $usersPas)) {
                http_response_code(400);
                echo json_encode(array("error" => "Entering personal information incorrect"));
                die();
            } else {
                session_start();
                $token = Tokens::createRandomToken();
                $_SESSION['user_data'] = [
                    'sid' => $token,
                    'userId' => $userId,
                    'email' => $email
                ];
                Tokens::createTokenDB($_SESSION['user_data']['sid'], $userId);
                http_response_code(200);
                echo json_encode(array(
                    "user_email" => $email,
                    "token" => $token,
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
        session_start();
        $sid = $_SESSION['user_data']['sid'] ?? '';
        $userId = $_SESSION['user_data']['userId'] ?? '';

        AuthDbRequests::logoutDbRequest($userId, $sid);

        session_destroy();
        foreach ($_SESSION as $key => $value) {
            unset($_SESSION[$key]);
        }
        echo json_encode(array("message" => "You are logout from app"));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function resetPassword(): void
    {
        $email = $_GET['email'] ?? '';
        if (!ValidationData::checkEmailData($email)) {
            http_response_code(400);
            echo json_encode(array("error" => "Entering personal information incorrect"));
            die();
        } else {
            $data = AuthDbRequests::resetPassDbRequest($email);
            if (!$data) {
                http_response_code(404);
                echo json_encode(array("message" => "User not found"));
                die();
            } else {
                session_start();
                $_SESSION['reset_email'] = $email;
                $token = Tokens::createRandomToken();
                $tempPass = $_SESSION['temporary_pass'] = GeneratePass::createPassword();

                ValidationData::checkTemporaryPassword($email, $token, $tempPass);

                $mail = new SendMailPassword();
                $mail->sendMail($email);

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
        session_start();
        $email = $_SESSION['reset_email'] ?? '';
        $temporaryPas = $_SESSION['temporary_pass'] ?? '';
        $cookiesToken = $_COOKIE['reset_pas'] ?? '';
        if (empty($cookiesToken)) {
            http_response_code(401);
            echo json_encode(array("message" => "You do not have access to this page"));
            die();
        }
        if (!empty($email) && !empty($temporaryPas)) {
            $data = AuthDbRequests::getPassDbRequest($email, $cookiesToken);
            if (isset($data['email']) && isset($data['cookies_token']) && isset($data['temporary_pass'])) {
                $newPass = $post['password'] ?? '';
                $newPassConfirm = $post['password_confirm'] ?? '';
                $userEmail = $data['email'];
                $userPas = $data['temporary_pass'];
                $userCookies = $data['cookies_token'];
                if ($newPass === $newPassConfirm) {
                    $passwordHash = password_hash($newPass, PASSWORD_BCRYPT);

                    AuthDbRequests::changePasDbRequest($passwordHash, $userEmail);
                    AuthDbRequests::deletePassDbRequest($userEmail, $userCookies, $userPas);

                    unset($_COOKIE['reset_pas']);
                    setcookie('reset_pas', "", -1, '/');
                } else {
                    http_response_code(401);
                    echo json_encode(array("message" => "Password mismatch"));
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
