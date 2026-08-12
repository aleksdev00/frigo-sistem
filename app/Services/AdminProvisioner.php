<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AdminRepository;

final readonly class AdminProvisioner
{
    public function __construct(private AdminRepository $admins)
    {
    }

    public function provision(string $username, string $password, string $confirmation): bool
    {
        $username = trim($username);
        if (!preg_match('/^[\p{L}\p{N}_.-]{3,100}$/u', $username)) {
            throw new \InvalidArgumentException('Username must be 3-100 letters, numbers, dots, dashes, or underscores.');
        }
        if ($password !== $confirmation) {
            throw new \InvalidArgumentException('Password confirmation does not match.');
        }
        if (strlen($password) < 12 || strlen($password) > 1024
            || !preg_match('/[a-z]/', $password)
            || !preg_match('/[A-Z]/', $password)
            || !preg_match('/\d/', $password)
            || !preg_match('/[^a-zA-Z0-9]/', $password)
        ) {
            throw new \InvalidArgumentException(
                'Password must be 12-1024 characters and include upper, lower, number, and symbol characters.',
            );
        }

        $algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        $hash = password_hash($password, $algorithm);
        if (!is_string($hash)) {
            throw new \RuntimeException('Password hashing failed.');
        }
        return $this->admins->createOrReplace($username, $hash);
    }
}
