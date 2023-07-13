<?php

namespace config;

/**
 * @return array<string>
 */
function configDb(): array
{
    return [
        'enable' => true,
        'host' => '127.0.0.1',
        'port' => '3306',
        'username' => 'root',
        'password' => '',
        'db' => 'file_storage'
    ];
}
