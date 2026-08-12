<?php

declare(strict_types=1);

use App\Foundation\Environment;

return [
    'name' => Environment::get('APP_NAME', 'Frigo Sistem'),
    'env' => Environment::get('APP_ENV', 'production'),
    'debug' => Environment::bool('APP_DEBUG', false),
    'url' => Environment::get('APP_URL', 'http://localhost'),
    'timezone' => Environment::get('APP_TIMEZONE', 'Europe/Belgrade'),
];
