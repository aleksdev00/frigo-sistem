<?php

declare(strict_types=1);

use App\Foundation\Environment;

return [
    'transport' => Environment::get('MAIL_TRANSPORT', 'log'),
    'host' => Environment::get('MAIL_HOST', ''),
    'port' => Environment::int('MAIL_PORT', 587),
    'username' => Environment::get('MAIL_USERNAME', ''),
    'password' => Environment::get('MAIL_PASSWORD', ''),
    'encryption' => Environment::get('MAIL_ENCRYPTION', 'tls'),
    'from_address' => Environment::get('MAIL_FROM_ADDRESS', ''),
    'from_name' => Environment::get('MAIL_FROM_NAME', 'Frigo Sistem'),
    'to_address' => Environment::get('CONTACT_TO_ADDRESS', ''),
    'rate_limit' => Environment::int('CONTACT_RATE_LIMIT', 5),
    'rate_window_seconds' => Environment::int('CONTACT_RATE_WINDOW_SECONDS', 3600),
    'minimum_fill_seconds' => Environment::int('CONTACT_MIN_FILL_SECONDS', 3),
];
