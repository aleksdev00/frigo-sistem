<?php

declare(strict_types=1);

namespace App\Services;

final readonly class SlugService
{
    private const SERBIAN = [
        'č' => 'c', 'ć' => 'c', 'đ' => 'dj', 'š' => 's', 'ž' => 'z',
        'Č' => 'c', 'Ć' => 'c', 'Đ' => 'dj', 'Š' => 's', 'Ž' => 'z',
    ];

    public function generate(string $value): string
    {
        $value = strtr(trim($value), self::SERBIAN);
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($converted)) {
                $value = $converted;
            }
        }
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }

    public function isValid(string $slug): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
    }
}
