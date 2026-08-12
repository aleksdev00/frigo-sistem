<?php

declare(strict_types=1);

namespace App\Security;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function token(): string
    {
        $token = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::SESSION_KEY] = $token;
        }

        return $token;
    }

    public function validate(mixed $submitted): bool
    {
        $stored = $_SESSION[self::SESSION_KEY] ?? null;
        return is_string($stored)
            && is_string($submitted)
            && strlen($submitted) === 64
            && hash_equals($stored, $submitted);
    }

    public function rotate(): string
    {
        unset($_SESSION[self::SESSION_KEY]);
        return $this->token();
    }
}
