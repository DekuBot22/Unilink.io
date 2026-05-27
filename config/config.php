<?php

declare(strict_types=1);

return [
    'db' => [
        'host'    => getenv('DB_HOST') ?: '127.0.0.1',
        'port'    => getenv('DB_PORT') ?: '3306',
        'name'    => getenv('DB_NAME') ?: 'unilink',
        'user'    => getenv('DB_USER') ?: 'root',
        'pass'    => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'UniLink',
    ],
    'google' => [
        'client_id'     => getenv('GOOGLE_CLIENT_ID') ?: '',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
    ],
    'daily' => [
        'api_key'   => getenv('DAILY_API_KEY') ?: '',
        'subdomain' => getenv('DAILY_SUBDOMAIN') ?: '',
    ],
    'mail' => [
        'host'      => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
        'port'      => (int) (getenv('MAIL_PORT') ?: 587),
        'username'  => getenv('MAIL_USERNAME') ?: '',
        'password'  => getenv('MAIL_PASSWORD') ?: '',
        'from'      => getenv('MAIL_FROM') ?: '',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'UniLink',
    ],
];
