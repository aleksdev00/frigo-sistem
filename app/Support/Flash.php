<?php

declare(strict_types=1);

namespace App\Support;

final class Flash
{
    private const KEY = '_admin_flash';

    public function success(string $message): void
    {
        $this->set('success', $message);
    }

    public function error(string $message): void
    {
        $this->set('error', $message);
    }

    public function pull(): ?array
    {
        $flash = $_SESSION[self::KEY] ?? null;
        unset($_SESSION[self::KEY]);
        return is_array($flash) ? $flash : null;
    }

    private function set(string $type, string $message): void
    {
        $_SESSION[self::KEY] = ['type' => $type, 'message' => strip_tags($message)];
    }
}
