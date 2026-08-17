<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;

final readonly class SmtpContactMailer implements ContactMailer
{
    public function __construct(private array $config) {}

    public function send(array $inquiry): void
    {
        foreach (['host','username','password','from_address','from_name','to_address'] as $key) {
            if (trim((string) ($this->config[$key] ?? '')) === '') throw new \RuntimeException('SMTP configuration is incomplete.');
        }
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string) $this->config['host'];
        $mail->Port = (int) ($this->config['port'] ?? 587);
        $mail->SMTPAuth = true;
        $mail->Username = (string) $this->config['username'];
        $mail->Password = (string) $this->config['password'];
        $encryption = strtolower(trim((string) ($this->config['encryption'] ?? 'tls')));
        $mail->SMTPSecure = match ($encryption) { 'ssl', 'smtps' => PHPMailer::ENCRYPTION_SMTPS, 'tls', 'starttls' => PHPMailer::ENCRYPTION_STARTTLS, 'none', '' => '', default => throw new \RuntimeException('Unsupported SMTP encryption setting.') };
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom((string) $this->config['from_address'], (string) $this->config['from_name']);
        $mail->addAddress((string) $this->config['to_address']);
        if ($inquiry['email'] !== '') $mail->addReplyTo($inquiry['email'], $this->headerText($inquiry['name']));
        $mail->Subject = $inquiry['product'] === null ? 'Novi upit sa sajta' : 'Upit za proizvod: ' . $this->headerText($inquiry['product']['name']);
        $mail->isHTML(false);
        $mail->Body = $this->body($inquiry);
        $mail->send();
    }

    private function body(array $inquiry): string
    {
        $product = $inquiry['product'];
        return "NOVI UPIT — FRIGO SISTEM\n\n"
            . 'Ime i prezime: ' . $inquiry['name'] . "\nEmail: " . ($inquiry['email'] ?: 'Nije naveden')
            . "\nTelefon: " . ($inquiry['phone'] ?: 'Nije naveden') . "\n"
            . ($product === null ? '' : 'Proizvod: ' . $product['name'] . "\nModel: " . ($product['code'] ?: 'Nije naveden') . "\n")
            . 'Vreme slanja: ' . $inquiry['submitted_at'] . "\n\nPoruka:\n" . $inquiry['message'];
    }

    private function headerText(string $value): string
    {
        return trim(str_replace(["\r", "\n"], ' ', $value));
    }
}
