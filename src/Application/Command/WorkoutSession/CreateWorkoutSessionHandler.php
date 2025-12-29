<?php

declare(strict_types=1);

namespace App\Application\Command\WorkoutSession;

use App\Domain\Entity\WorkoutSession;
use App\Domain\Repository\WorkoutSessionRepositoryInterface;

final readonly class CreateWorkoutSessionHandler
{
    public function __construct(
        private WorkoutSessionRepositoryInterface $workoutSessionRepository,
    ) {
    }

    public function handle(CreateWorkoutSessionCommand $command): void
    {
        $workoutSession = WorkoutSession::create(
            id: $command->id,
            user: $command->user,
            date: $command->date,
            name: $command->name,
            notes: $command->notes
        );

        $this->workoutSessionRepository->save($workoutSession);
    }
}
