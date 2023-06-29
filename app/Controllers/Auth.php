<?php

namespace Controllers;

use app\Database\Database;
use app\Services\CheckTokens;
use app\Services\GeneratePass;
use app\Services\Roles;
use app\Services\SendMailPassword;
use app\Services\ValidationData;
use function app\Controllers\setcookie;

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
        $db = Database::connect();
        $name = $data["name"] ?? "";
        $login = $data["login"] ?? "";
        $email = $data["email"] ?? "";
        $password = $data['password'] ?? "";
        $pasConfirm = $data['password_confirm'] ?? "";

        if (!ValidationData::filterNameData($name) || !ValidationData::filterNameData($login) || !ValidationData::filterEmailData($email)) {
            http_response_code(400);
            echo json_encode(array("error" => "Entering personal information incorrect"));
            die();
        }
        if (!ValidationData::checkEmailExistence($db, $email)) {
            http_response_code(400);
            echo json_encode(array("error" => "Email {$email} is already exist"));
            die();
        }
        if ($password === $pasConfirm) {
            $passwordHash = password_hash($data["password"], PASSWORD_BCRYPT);

            $sql = "INSERT INTO `users` (`id`, `name`, `login`, `email`, `password`, `date_created`) VALUES (null, :name, :login, :email, :password, NOW())";
            $statement = $db->prepare($sql);
            $statement->execute(['name' => $name, 'login' => $login, 'email' => $email, 'password' => $passwordHash]);

            Roles::addRoles($db, $email);

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
        $db = Database::connect();
        $email = $data['email'] ?? "";
        $password = $data['password'] ?? "";

        $sql = "SELECT * FROM `users` WHERE `email` = :email";
        $statement = $db->prepare($sql);
        $statement->execute(['email' => $email]);
        $users = $statement->fetch();
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
                $token = GeneratePass::createToken();
                $_SESSION['user_data'] = [
                    'sid' => $token,
                    'userId' => $userId,
                    'email' => $email
                ];
                CheckTokens::createTokenDB($db, $_SESSION['user_data']['sid'], $userId);
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
        $db = Database::connect();
        $sid = $_SESSION['user_data']['sid'] ?? '';
        $userId = $_SESSION['user_data']['userId'] ?? '';
        $db->query("DELETE FROM `tokens` WHERE user_id = '$userId' AND token = '$sid'");
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
        $db = Database::connect();
        $email = $_GET['email'] ?? '';
        if (!ValidationData::filterEmailData($email)) {
            http_response_code(400);
            echo json_encode(array("error" => "Entering personal information incorrect"));
            die();
        } else {
            $sql = "SELECT `password` FROM `users` WHERE `email` = :email";
            $statement = $db->prepare($sql);
            $statement->execute(['email' => $email]);
            $data = $statement->fetch(\PDO::FETCH_ASSOC);
            if (!$data) {
                http_response_code(404);
                echo json_encode(array("message" => "User not found"));
                die();
            } else {
                session_start();
                $_SESSION['reset_email'] = $email;
                $token = GeneratePass::createToken();
                $tempPass = $_SESSION['temporary_pass'] = GeneratePass::createPassword();

                CheckTokens::checkTemporaryPassword($db, $email, $token, $tempPass);

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
        $db = Database::connect();
        $email = $_SESSION['reset_email'] ?? '';
        $temporaryPas = $_SESSION['temporary_pass'] ?? '';
        $cookiesToken = $_COOKIE['reset_pas'] ?? '';

        if (empty($cookiesToken)) {
            http_response_code(401);
            echo json_encode(array("message" => "You do not have access to this page"));
            die();
        }
        if (!empty($email) && !empty($temporaryPas)) {
            $sql = "SELECT `email`, `cookies_token`, `temporary_pass`  FROM `reset_pas` WHERE `email` = :email AND `cookies_token` = :cookiesToken AND `temporary_pass` = '$temporaryPas'";
            $statement = $db->prepare($sql);
            $statement->execute(['email' => $email, 'cookiesToken' => $cookiesToken]);
            $data = $statement->fetch(\PDO::FETCH_ASSOC);
            if (isset($data['email']) && isset($data['cookies_token']) && isset($data['temporary_pass'])) {
                $newPass = $post['password'] ?? '';
                $newPassConfirm = $post['password_confirm'] ?? '';
                $userEmail = $data['email'];
                $userPas = $data['temporary_pass'];
                $userCookies = $data['cookies_token'];

                if ($newPass === $newPassConfirm) {
                    $passwordHash = password_hash($newPass, PASSWORD_BCRYPT);

                    $statement = $db->prepare("UPDATE `users` SET `password` = '$passwordHash' WHERE `email` = :email");
                    $statement->execute(['email' => $userEmail]);

                    $db->query("DELETE FROM `reset_pas` WHERE `email` = '$userEmail' AND `cookies_token` = '$userCookies' AND `temporary_pass` = '$userPas'");

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
