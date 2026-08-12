<?php

declare(strict_types=1);

namespace App\Http;

final readonly class Response
{
    public function __construct(
        public string $body,
        public int $status = 200,
        public array $headers = [],
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function redirect(string $location, int $status = 303): self
    {
        if (!str_starts_with($location, '/')) {
            throw new \InvalidArgumentException('Redirect locations must be application-relative.');
        }

        return new self('', $status, ['Location' => $location]);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->effectiveHeaders() as $name => $value) {
            header($name . ': ' . $value, true);
        }
        echo $this->body;
    }

    public function effectiveHeaders(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'",
            ...$this->headers,
        ];
    }
}
