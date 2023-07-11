<?php

namespace app\HTTP\Response;

use app\HTTP\Interface\ResponseInterface;

class Response implements ResponseInterface
{
    public const HTTP_OK = 200;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;

    public const NOT_FOUND = 404;
    public const SERVICE_UNAVAILABLE = 503;

    public static array $textErrors = [
        1 => 'Failed with entry information',
        2 => 'Directory is already exist',
        3 => 'Page access denied',
        4 => 'Directory does not exist',
        5 => 'File does not exist',
        6 => 'File error',
        7 => 'File is already exist. Rename file',
        8 => 'User already has access to the file',
        9 => 'User has not access to the file',
        10 => 'User not found',
        11 => 'User deleted',
        12 => 'User updated',
        13 => 'Cancel operation. Data error',
        14 => 'Incorrectly entered personal information',
        15 => 'Email is already exist. Rename email',
        16 => 'User created',
        17 => 'You are logout from app',
        18 => 'Password mismatch',
        19 => 'Password changed successfully',
        20 => 'Error in data processing on the server',




    ];

    protected static mixed $content;
    protected static int $statusCode;

    /**
     * @param int $code
     * @return int|bool
     */
    public static function setStatusCode(int $code): int|bool
    {
        return self::$statusCode = http_response_code($code);
    }

    /**
     * @param mixed $content
     * @return void
     */
    public static function setContent(mixed $content): void
    {
        self::$content = $content ?? '';
        echo json_encode(array('message' => self::$content));
    }
}
