<?php

declare(strict_types=1);

namespace App\Validation;

final readonly class ValidationResult
{
    public function __construct(public array $data, public array $errors)
    {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }
}
