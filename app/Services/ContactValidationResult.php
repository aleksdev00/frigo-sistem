<?php

declare(strict_types=1);

namespace App\Services;

final readonly class ContactValidationResult
{
    public function __construct(public array $values, public array $errors) {}

    public function isValid(): bool
    {
        return $this->errors === [];
    }
}
