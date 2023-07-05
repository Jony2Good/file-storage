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
        return ['token' => $sid, 'id' => $userId];
    }
}