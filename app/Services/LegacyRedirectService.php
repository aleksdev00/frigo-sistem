<?php

declare(strict_types=1);

namespace App\Services;

final readonly class LegacyRedirectService
{
    public function __construct(private array $mappings)
    {
        foreach ($mappings as $old => $new) {
            if (!$this->validPath($old) || !$this->validPath($new) || $old === $new || array_key_exists($new, $mappings)) {
                throw new \InvalidArgumentException('Legacy redirects must be exact, local, single-hop path mappings.');
            }
        }
    }

    public function destination(string $path): ?string
    {
        $destination = $this->mappings[$path] ?? null;
        return is_string($destination) ? $destination : null;
    }

    private function validPath(mixed $path): bool
    {
        return is_string($path) && str_starts_with($path, '/') && !str_starts_with($path, '//')
            && !str_contains($path, '?') && !str_contains($path, '#') && !str_contains($path, "\0");
    }
}
