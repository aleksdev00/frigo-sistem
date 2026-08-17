<?php

declare(strict_types=1);

namespace App\Services;

final readonly class ContactRateLimiter
{
    public function __construct(private string $directory, private string $secret, private int $limit = 5, private int $windowSeconds = 3600) {}

    public function consume(string $clientIp, ?int $now = null): bool
    {
        $now ??= time();
        $identifier = hash_hmac('sha256', 'contact-ip:' . ($clientIp === '' ? 'unknown' : $clientIp), $this->secret);
        if (!is_dir($this->directory) && !mkdir($this->directory, 0750, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Contact rate-limit directory is unavailable.');
        }
        $path = $this->directory . '/' . $identifier . '.json';
        $handle = fopen($path, 'c+');
        if ($handle === false) throw new \RuntimeException('Contact rate-limit state is unavailable.');
        try {
            if (!flock($handle, LOCK_EX)) throw new \RuntimeException('Contact rate-limit state could not be locked.');
            $raw = stream_get_contents($handle);
            $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
            $attempts = is_array($decoded) ? array_values(array_filter($decoded, fn (mixed $value): bool => is_int($value) && $value > $now - $this->windowSeconds)) : [];
            if (count($attempts) >= $this->limit) return false;
            $attempts[] = $now;
            rewind($handle);
            if (!ftruncate($handle, 0) || fwrite($handle, json_encode($attempts, JSON_THROW_ON_ERROR)) === false) throw new \RuntimeException('Contact rate-limit state could not be saved.');
            fflush($handle);
            return true;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
