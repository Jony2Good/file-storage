<?php

namespace app\Services;

use app\Services\Interface\SessionService;

class CreateSession implements SessionService
{

    public static function start(): array|string
    {
        session_start();
        $sid = $_SESSION['user_data']['sid'] ?? "";
        $userId = $_SESSION['user_data']['userId'] ?? "";
        $email = $_SESSION['reset_email'] ?? '';
        $temporaryPas = $_SESSION['temporary_pass'] ?? '';
        $cookiesToken = $_COOKIE['reset_pas'] ?? '';
        return [
            'token' => $sid,
            'id' => $userId,
            'resetEmail' => $email,
            'tempPas' => $temporaryPas,
            'cookies' => $cookiesToken];
    }

    public static function end(): void
    {
        session_destroy();
        foreach ($_SESSION as $key => $value) {
            unset($_SESSION[$key]);
        }
    }

}