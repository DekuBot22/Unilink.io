<?php
// Copia este archivo como config.php y rellena los valores reales.

return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => '3306',
        'name'    => 'unilink',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'UniLink',
    ],
    'google' => [
        'client_id'     => 'TU_GOOGLE_CLIENT_ID',
        'client_secret' => 'TU_GOOGLE_CLIENT_SECRET',
    ],
    'daily' => [
        'api_key'   => 'TU_DAILY_API_KEY',
        'subdomain' => 'tu-subdominio',
    ],
    'mail' => [
        'host'      => 'smtp.gmail.com',
        'port'      => 587,
        'username'  => 'tu-correo@gmail.com',
        'password'  => 'TU_APP_PASSWORD',
        'from'      => 'tu-correo@gmail.com',
        'from_name' => 'UniLink',
    ],
];
