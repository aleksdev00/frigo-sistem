<?php

declare(strict_types=1);

namespace App\Services;

final readonly class ContactAntiSpam
{
    public function __construct(private string $secret, private int $minimumSeconds = 3, private int $maximumSeconds = 7200) {}

    /** @return array{started:string,signature:string} */
    public function fields(?int $now = null): array
    {
        $started = (string) ($now ?? time());
        return ['started' => $started, 'signature' => hash_hmac('sha256', 'contact:' . $started, $this->secret)];
    }

    public function accepts(array $input, ?int $now = null): bool
    {
        if (is_string($input['website'] ?? null) && trim($input['website']) !== '') return false;
        $started = $input['_form_started'] ?? null;
        $signature = $input['_form_signature'] ?? null;
        if (!is_string($started) || preg_match('/^[0-9]{1,12}$/D', $started) !== 1 || !is_string($signature)) return false;
        $expected = hash_hmac('sha256', 'contact:' . $started, $this->secret);
        if (!hash_equals($expected, $signature)) return false;
        $age = ($now ?? time()) - (int) $started;
        return $age >= $this->minimumSeconds && $age <= $this->maximumSeconds;
    }
}
