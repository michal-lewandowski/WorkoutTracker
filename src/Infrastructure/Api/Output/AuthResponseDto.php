<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

final readonly class AuthResponseDto
{
    public function __construct(
        public UserDto $user,
        public string $token,
    ) {}
}

