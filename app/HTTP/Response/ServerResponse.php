<?php

namespace app\HTTP\Response;

class ServerResponse extends Response
{
    /**
     * @param int $mode
     * @param int $code
     * @return void
     */
    public static function createResponse(int $mode, int $code = 200): void
    {
        self::setContent(self::$textErrors[$mode]);
        switch ($code) {
            case 200:
                self::setStatusCode(self::HTTP_OK);
                break;
            case 400:
                self::setStatusCode(self::HTTP_BAD_REQUEST);
                break;
            case 401:
                self::setStatusCode(self::HTTP_UNAUTHORIZED);
                break;
            case 404:
                self::setStatusCode(self::NOT_FOUND);
                break;
            case 503:
                self::setStatusCode(self::SERVICE_UNAVAILABLE);
                break;
        }
    }

    /**
     * @param array|string $data
     * @param int $code
     * @return void
     */
    public static function createResponseList(array|string $data, int $code = self::HTTP_OK): void
    {
        self::setStatusCode($code);
        self::setContent($data);

    }

}
