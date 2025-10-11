<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

use App\Domain\Entity\User;

final readonly class UserDto
{
    public function __construct(
        public string $id,
        public string $email,
        public \DateTimeImmutable $createdAt,
    ) {}
    
    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->getId(),
            email: $user->getEmail(),
            createdAt: $user->getCreatedAt(),
        );
    }
}

