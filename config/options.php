<?php

namespace config;

/**
 * @return array<string>
 */
function dbOptions(): array
{
    return [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_STRINGIFY_FETCHES => false,
        \PDO::ATTR_EMULATE_PREPARES => false
    ];
}
