<?php

declare(strict_types=1);

namespace App\Repositories;

use DateTimeImmutable;
use PDO;

final readonly class LoginThrottleRepository
{
    public function __construct(
        private PDO $pdo,
        private int $maxFailures = 5,
        private int $windowSeconds = 900,
        private int $blockSeconds = 900,
    ) {
    }

    /** @param list<string> $identifiers */
    public function isBlocked(array $identifiers, DateTimeImmutable $now): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT blocked_until FROM login_throttles WHERE identifier_hash = :identifier_hash',
        );
        foreach ($identifiers as $identifier) {
            $statement->execute(['identifier_hash' => $identifier]);
            $blockedUntil = $statement->fetchColumn();
            if (is_string($blockedUntil) && $blockedUntil > $now->format('Y-m-d H:i:s')) {
                return true;
            }
        }
        return false;
    }

    /** @param list<string> $identifiers */
    public function recordFailure(array $identifiers, DateTimeImmutable $now): void
    {
        $this->pdo->beginTransaction();
        try {
            foreach ($identifiers as $identifier) {
                $this->recordOne($identifier, $now);
            }
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }

        if (random_int(1, 100) === 1) {
            $cutoff = $now->modify('-2 days')->format('Y-m-d H:i:s');
            $cleanup = $this->pdo->prepare('DELETE FROM login_throttles WHERE updated_at < :cutoff LIMIT 100');
            $cleanup->execute(['cutoff' => $cutoff]);
        }
    }

    /** @param list<string> $identifiers */
    public function clear(array $identifiers): void
    {
        $statement = $this->pdo->prepare('DELETE FROM login_throttles WHERE identifier_hash = :identifier_hash');
        foreach ($identifiers as $identifier) {
            $statement->execute(['identifier_hash' => $identifier]);
        }
    }

    private function recordOne(string $identifier, DateTimeImmutable $now): void
    {
        $select = $this->pdo->prepare(
            'SELECT failure_count, first_attempt_at FROM login_throttles '
            . 'WHERE identifier_hash = :identifier_hash FOR UPDATE',
        );
        $select->execute(['identifier_hash' => $identifier]);
        $row = $select->fetch();
        $nowSql = $now->format('Y-m-d H:i:s');

        if (!is_array($row)) {
            $insert = $this->pdo->prepare(
                'INSERT INTO login_throttles '
                . '(identifier_hash, failure_count, first_attempt_at, last_attempt_at, blocked_until, updated_at) '
                . 'VALUES (:identifier_hash, 1, :first_attempt_at, :last_attempt_at, NULL, :updated_at)',
            );
            $insert->execute([
                'identifier_hash' => $identifier,
                'first_attempt_at' => $nowSql,
                'last_attempt_at' => $nowSql,
                'updated_at' => $nowSql,
            ]);
            return;
        }

        $windowExpired = (new DateTimeImmutable((string) $row['first_attempt_at']))
            ->modify('+' . $this->windowSeconds . ' seconds') <= $now;
        $count = $windowExpired ? 1 : (int) $row['failure_count'] + 1;
        $firstAttempt = $windowExpired ? $nowSql : (string) $row['first_attempt_at'];
        $blockedUntil = $count >= $this->maxFailures
            ? $now->modify('+' . $this->blockSeconds . ' seconds')->format('Y-m-d H:i:s')
            : null;

        $update = $this->pdo->prepare(
            'UPDATE login_throttles SET failure_count = :failure_count, first_attempt_at = :first_attempt_at, '
            . 'last_attempt_at = :last_attempt_at, blocked_until = :blocked_until, updated_at = :updated_at '
            . 'WHERE identifier_hash = :identifier_hash',
        );
        $update->execute([
            'failure_count' => $count,
            'first_attempt_at' => $firstAttempt,
            'last_attempt_at' => $nowSql,
            'updated_at' => $nowSql,
            'blocked_until' => $blockedUntil,
            'identifier_hash' => $identifier,
        ]);
    }
}
