<?php

declare(strict_types=1);

namespace App\Foundation;

final class Environment
{
    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new \RuntimeException('Unable to read the environment file.');
        }

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = substr($line, 7);
            }

            $separator = strpos($line, '=');
            if ($separator === false) {
                throw new \RuntimeException(sprintf('Invalid environment entry on line %d.', $lineNumber + 1));
            }

            $key = trim(substr($line, 0, $separator));
            if (!preg_match('/^[A-Z_][A-Z0-9_]*$/i', $key)) {
                throw new \RuntimeException(sprintf('Invalid environment key on line %d.', $lineNumber + 1));
            }

            if (getenv($key) !== false || array_key_exists($key, $_ENV)) {
                continue;
            }

            $value = self::parseValue(trim(substr($line, $separator + 1)), $lineNumber + 1);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return $value === false || $value === null ? $default : (string) $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($parsed === null) {
            throw new \RuntimeException(sprintf('%s must be a boolean value.', $key));
        }

        return $parsed;
    }

    public static function int(string $key, int $default): int
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        if ($parsed === false) {
            throw new \RuntimeException(sprintf('%s must be an integer.', $key));
        }

        return $parsed;
    }

    private static function parseValue(string $value, int $lineNumber): string
    {
        if ($value === '') {
            return '';
        }

        $quote = $value[0];
        if ($quote !== '"' && $quote !== "'") {
            $comment = preg_replace('/\s+#.*$/', '', $value);
            return trim($comment ?? $value);
        }

        if (strlen($value) < 2 || !str_ends_with($value, $quote)) {
            throw new \RuntimeException(sprintf('Unclosed quoted value on line %d.', $lineNumber));
        }

        $unquoted = substr($value, 1, -1);
        return $quote === '"' ? stripcslashes($unquoted) : $unquoted;
    }
}
