<?php

declare(strict_types=1);

namespace App\Application\Command\Auth;

use App\Infrastructure\Api\Input\RegisterRequestDto;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $plainPassword,
    ) {
    }

    public static function fromDto(RegisterRequestDto $dto): self
    {
        return new self(
            email: $dto->email,
            plainPassword: $dto->password,
        );
    }
}
