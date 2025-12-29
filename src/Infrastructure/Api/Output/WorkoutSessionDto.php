<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

final readonly class WorkoutSessionDto
{
    public function __construct(
        public string $id,
        public string $date,
        public ?string $name,
        public ?string $notes,
        public \DateTimeImmutable $createdAt,
        public int $exerciseCount,
    ) {
    }
}
