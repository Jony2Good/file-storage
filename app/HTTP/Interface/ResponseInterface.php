<?php

namespace app\HTTP\Interface;

interface ResponseInterface
{
    public static function setStatusCode(int $code): int|bool;

    public static function setContent(mixed $content): void;

}
