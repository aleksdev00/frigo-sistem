<?php

declare(strict_types=1);

namespace App\Http;

final readonly class Request
{
    public function __construct(public string $method, public string $path)
    {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);

        return new self($method, self::normalizePath(is_string($path) ? $path : '/'));
    }

    public static function normalizePath(string $path): string
    {
        $decoded = rawurldecode($path);
        if (str_contains($decoded, "\0")) {
            return '/';
        }

        $normalized = '/' . trim($decoded, '/');
        return $normalized === '/' ? '/' : rtrim($normalized, '/');
    }
}
