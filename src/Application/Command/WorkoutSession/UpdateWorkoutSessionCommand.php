<?php

declare(strict_types=1);

namespace App\Application\Command\WorkoutSession;

final readonly class UpdateWorkoutSessionCommand
{
    public function __construct(
        public string $id,
        public string $userId,
        public \DateTimeImmutable $date,
        public ?string $name,
        public ?string $notes,
    ) {
    }
}
