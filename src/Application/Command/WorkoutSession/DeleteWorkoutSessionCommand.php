<?php

declare(strict_types=1);

namespace App\Application\Command\WorkoutSession;

use App\Domain\Entity\User;
use App\Domain\Entity\WorkoutSession;

final readonly class DeleteWorkoutSessionCommand
{
    public function __construct(
        public WorkoutSession $workoutSession,
        public User $deletedBy,
    ) {
    }
}
