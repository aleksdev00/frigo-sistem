<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final readonly class AdminRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, username, password_hash, is_active FROM admins WHERE username = :username LIMIT 1',
        );
        $statement->execute(['username' => $username]);
        $admin = $statement->fetch();
        return is_array($admin) ? $admin : null;
    }

    public function recordSuccessfulLogin(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE admins SET last_login_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id',
        );
        $statement->execute(['id' => $id]);
    }

    public function createOrReplace(string $username, string $passwordHash): bool
    {
        $existing = $this->findByUsername($username);
        if ($existing === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO admins (username, password_hash, is_active, created_at, updated_at) '
                . 'VALUES (:username, :password_hash, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
            );
            $statement->execute(['username' => $username, 'password_hash' => $passwordHash]);
            return true;
        }

        $statement = $this->pdo->prepare(
            'UPDATE admins SET password_hash = :password_hash, is_active = 1, updated_at = CURRENT_TIMESTAMP '
            . 'WHERE id = :id',
        );
        $statement->execute(['password_hash' => $passwordHash, 'id' => $existing['id']]);
        return false;
    }
}
