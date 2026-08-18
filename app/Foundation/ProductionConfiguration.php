<?php

declare(strict_types=1);

namespace App\Foundation;

final class ProductionConfiguration
{
    public static function validate(Config $config): void
    {
        if (strtolower((string) $config->get('app.env', 'production')) !== 'production') {
            return;
        }

        if ((bool) $config->get('app.debug', false)) {
            throw new \RuntimeException('APP_DEBUG must be false in production.');
        }

        $appUrl = rtrim((string) $config->get('app.url', ''), '/');
        $parts = parse_url($appUrl);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || !isset($parts['host'])
            || isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment'])
            || !in_array($parts['path'] ?? '', ['', '/'], true)
        ) {
            throw new \RuntimeException('APP_URL must be the canonical HTTPS origin in production.');
        }

        if (strlen((string) $config->get('app.key', '')) < 32) {
            throw new \RuntimeException('APP_KEY must contain at least 32 characters in production.');
        }

        $idleTimeout = (int) $config->get('app.session_idle_timeout', 0);
        if ($idleTimeout < 300 || $idleTimeout > 86400) {
            throw new \RuntimeException('SESSION_IDLE_TIMEOUT must be between 300 and 86400 seconds in production.');
        }

        foreach (['host', 'database', 'username', 'password'] as $key) {
            if (trim((string) $config->get('database.' . $key, '')) === '') {
                throw new \RuntimeException('Production database configuration is incomplete.');
            }
        }

        $mail = (array) $config->get('mail', []);
        if (strtolower((string) ($mail['transport'] ?? '')) !== 'smtp') {
            throw new \RuntimeException('Production contact mail requires MAIL_TRANSPORT=smtp.');
        }
        foreach (['host', 'username', 'password', 'from_address', 'from_name', 'to_address'] as $key) {
            if (trim((string) ($mail[$key] ?? '')) === '') {
                throw new \RuntimeException('Production SMTP configuration is incomplete.');
            }
        }
        foreach (['from_address', 'to_address'] as $key) {
            if (filter_var((string) $mail[$key], FILTER_VALIDATE_EMAIL) === false) {
                throw new \RuntimeException('Production mail addresses must be valid.');
            }
        }

        foreach (['pdo_mysql', 'fileinfo', 'gd'] as $extension) {
            if (!extension_loaded($extension)) {
                throw new \RuntimeException('A required production PHP extension is unavailable.');
            }
        }
        if (!function_exists('imagewebp')) {
            throw new \RuntimeException('Production GD must include WebP support.');
        }
    }
}
