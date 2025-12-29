<?php

declare(strict_types=1);

namespace App\Application\Command\WorkoutSession;

use App\Domain\Exception\WorkoutSessionNotFoundException;
use App\Domain\Repository\WorkoutSessionRepositoryInterface;

final readonly class UpdateWorkoutSessionHandler
{
    public function __construct(
        private WorkoutSessionRepositoryInterface $workoutSessionRepository,
    ) {
    }

    public function handle(UpdateWorkoutSessionCommand $command): void
    {
        $workoutSession = $this->workoutSessionRepository->findByIdWithExercises(
            id: $command->id,
            userId: $command->userId
        );

        if (null === $workoutSession) {
            throw WorkoutSessionNotFoundException::withId($command->id);
        }

        $workoutSession->update(
            date: $command->date,
            name: $command->name,
            notes: $command->notes
        );

        $this->workoutSessionRepository->save($workoutSession);
    }
}
