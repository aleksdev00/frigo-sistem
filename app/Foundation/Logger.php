<?php

declare(strict_types=1);

namespace App\Foundation;

final readonly class Logger
{
    public function __construct(private string $path)
    {
    }

    public function error(string $message, array $context = []): void
    {
        $directory = dirname($this->path);
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            error_log('Application log directory could not be created.');
            return;
        }

        $record = sprintf(
            "[%s] ERROR: %s %s%s",
            date(DATE_ATOM),
            str_replace(["\r", "\n"], ' ', $message),
            $context === [] ? '' : json_encode($this->redact($context), JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
            PHP_EOL,
        );

        if (file_put_contents($this->path, $record, FILE_APPEND | LOCK_EX) === false) {
            error_log('Application error could not be written to its log.');
        }
    }

    private function redact(array $context): array
    {
        $sensitive = ['password', 'secret', 'token', 'authorization', 'cookie', 'session', 'credential'];
        foreach ($context as $key => $value) {
            $normalized = strtolower((string) $key);
            if (array_any($sensitive, static fn (string $term): bool => str_contains($normalized, $term))) {
                $context[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $context[$key] = $this->redact($value);
            }
        }

        return $context;
    }
}
