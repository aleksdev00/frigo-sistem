<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AdminRepository;
use App\Repositories\LoginThrottleRepository;
use App\Security\SessionManager;
use DateTimeImmutable;

final readonly class AuthService
{
    private const SESSION_KEY = 'admin_auth';
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

    public function __construct(
        private AdminRepository $admins,
        private LoginThrottleRepository $throttle,
        private SessionManager $sessions,
        private string $identifierSecret,
    ) {
    }

    public function attempt(string $username, string $password, string $clientIp): bool
    {
        $username = trim($username);
        $identifiers = $this->identifiers($username, $clientIp);
        $now = new DateTimeImmutable();

        if ($this->throttle->isBlocked($identifiers, $now)) {
            password_verify($password, self::DUMMY_HASH);
            return false;
        }

        $admin = $username === '' ? null : $this->admins->findByUsername($username);
        $hash = is_array($admin) ? (string) $admin['password_hash'] : self::DUMMY_HASH;
        $valid = password_verify($password, $hash)
            && is_array($admin)
            && (int) $admin['is_active'] === 1;

        if (!$valid) {
            $this->throttle->recordFailure($identifiers, $now);
            return false;
        }

        $this->sessions->regenerate();
        $_SESSION[self::SESSION_KEY] = [
            'id' => (int) $admin['id'],
            'username' => (string) $admin['username'],
            'authenticated_at' => time(),
            'last_activity_at' => time(),
        ];
        $this->admins->recordSuccessfulLogin((int) $admin['id']);
        $this->throttle->clear($identifiers);
        return true;
    }

    public function isAuthenticated(): bool
    {
        $auth = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($auth) || !is_int($auth['id'] ?? null) || !is_string($auth['username'] ?? null)) {
            return false;
        }
        if (!$this->admins->isActive($auth['id'])) {
            $this->logout();
            return false;
        }
        $_SESSION[self::SESSION_KEY]['last_activity_at'] = time();
        return true;
    }

    public function currentAdminId(): ?int
    {
        return $this->isAuthenticated() ? $_SESSION[self::SESSION_KEY]['id'] : null;
    }

    public function currentUsername(): ?string
    {
        return $this->isAuthenticated() ? $_SESSION[self::SESSION_KEY]['username'] : null;
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        $this->sessions->destroy();
    }

    /** @return list<string> */
    private function identifiers(string $username, string $clientIp): array
    {
        return [
            hash_hmac('sha256', 'username:' . strtolower($username), $this->identifierSecret),
            hash_hmac('sha256', 'ip:' . ($clientIp === '' ? 'unknown' : $clientIp), $this->identifierSecret),
        ];
    }
}
