<?php

namespace config;
/**
 * @return array<string>
 */
function mailOptions(): array
{
    return [
        'smtp_auth' => true,
        'smtp_secure' => 'tls',
        'port' => 587,
        'host' => 'smtp.yandex.ru',
        'username' => 'test1ng2023@yandex.ru',
        'password' => 'theaftuguttnxewi',
        'from_email' => 'test1ng2023@yandex.ru',
        'name_app' => 'My files',
        'subject' => 'Rest password',
    ];
}
