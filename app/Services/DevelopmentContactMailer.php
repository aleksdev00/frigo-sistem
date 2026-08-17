<?php

declare(strict_types=1);

namespace App\Services;

final readonly class DevelopmentContactMailer implements ContactMailer
{
    public function __construct(private string $directory) {}

    public function send(array $inquiry): void
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0750, true) && !is_dir($this->directory)) throw new \RuntimeException('Development mail directory is unavailable.');
        $product = $inquiry['product'];
        $body = "NOVI UPIT — FRIGO SISTEM\n\n"
            . 'Ime i prezime: ' . $inquiry['name'] . "\n"
            . 'Email: ' . ($inquiry['email'] ?: 'Nije naveden') . "\n"
            . 'Telefon: ' . ($inquiry['phone'] ?: 'Nije naveden') . "\n"
            . ($product === null ? '' : 'Proizvod: ' . $product['name'] . "\nModel: " . ($product['code'] ?: 'Nije naveden') . "\n")
            . 'Vreme slanja: ' . $inquiry['submitted_at'] . "\n\nPoruka:\n" . $inquiry['message'] . "\n";
        $path = $this->directory . '/contact-' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.txt';
        if (file_put_contents($path, $body, LOCK_EX) === false) throw new \RuntimeException('Development mail preview could not be written.');
        @chmod($path, 0640);
    }
}
