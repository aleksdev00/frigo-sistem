<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;

final readonly class ContactService
{
    public const NAME_MAX = 120;
    public const EMAIL_MAX = 254;
    public const PHONE_MAX = 50;
    public const MESSAGE_MIN = 10;
    public const MESSAGE_MAX = 5000;

    public function __construct(private ContactMailer $mailer) {}

    public function validate(array $input): ContactValidationResult
    {
        $values = [
            'name' => $this->string($input['name'] ?? ''),
            'email' => $this->string($input['email'] ?? ''),
            'phone' => $this->string($input['phone'] ?? ''),
            'message' => $this->string($input['message'] ?? ''),
        ];
        $errors = [];

        if ($values['name'] === '') $errors['name'] = 'Unesite ime i prezime.';
        elseif ($this->length($values['name']) > self::NAME_MAX) $errors['name'] = 'Ime i prezime može imati najviše 120 znakova.';

        if ($this->length($values['email']) > self::EMAIL_MAX) $errors['email'] = 'Email adresa je predugačka.';
        elseif ($values['email'] !== '' && filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) $errors['email'] = 'Unesite ispravnu email adresu.';

        if ($this->length($values['phone']) > self::PHONE_MAX) $errors['phone'] = 'Telefon može imati najviše 50 znakova.';
        elseif ($values['phone'] !== '' && preg_match('/^[0-9+()\.\/\s-]+$/uD', $values['phone']) !== 1) $errors['phone'] = 'Unesite ispravan broj telefona.';

        if ($values['email'] === '' && $values['phone'] === '') {
            $errors['email'] = 'Unesite email adresu ili broj telefona.';
            $errors['phone'] = 'Unesite broj telefona ili email adresu.';
        }

        $messageLength = $this->length($values['message']);
        if ($messageLength === 0) $errors['message'] = 'Unesite poruku.';
        elseif ($messageLength < self::MESSAGE_MIN) $errors['message'] = 'Poruka mora imati najmanje 10 znakova.';
        elseif ($messageLength > self::MESSAGE_MAX) $errors['message'] = 'Poruka može imati najviše 5.000 znakova.';

        return new ContactValidationResult($values, $errors);
    }

    public function deliver(array $values, ?array $product, ?DateTimeImmutable $submittedAt = null): void
    {
        $this->mailer->send([
            ...$values,
            'submitted_at' => ($submittedAt ?? new DateTimeImmutable())->format(DATE_ATOM),
            'product' => $product === null ? null : [
                'name' => (string) $product['name'],
                'code' => trim((string) ($product['code'] ?? '')),
                'slug' => (string) $product['slug'],
            ],
        ]);
    }

    private function string(mixed $value): string
    {
        return is_string($value) ? trim(str_replace("\0", '', $value)) : '';
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}
