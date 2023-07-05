<?php

namespace app\Database\Interface;

interface Requests
{
    public static function read(string $statement, ?array $data, string $mode);

    public static function write(string $statement, array $data): bool;

    public static function query(string $statement): void;
}
