<?php

declare(strict_types=1);

namespace App\Application\Command\WorkoutSession;

use App\Domain\Entity\User;
use Symfony\Component\Uid\Uuid;

final readonly class CreateWorkoutSessionCommand
{
    public function __construct(
        public Uuid $id,
        public User $user,
        public \DateTimeImmutable $date,
        public ?string $name,
        public ?string $notes,
    ) {
    }
}
