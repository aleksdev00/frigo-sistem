<?php

declare(strict_types=1);

use App\Foundation\Environment;

return [
    'driver' => 'mysql',
    'host' => Environment::get('DB_HOST', '127.0.0.1'),
    'port' => Environment::int('DB_PORT', 3306),
    'database' => Environment::get('DB_DATABASE', 'frigo_sistem'),
    'username' => Environment::get('DB_USERNAME', 'frigo_sistem'),
    'password' => Environment::get('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
];
