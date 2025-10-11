<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

final readonly class ValidationErrorDto
{
    public function __construct(
        public string $message,
        public array $errors,
    ) {}
}

