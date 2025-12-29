<?php

declare(strict_types=1);

namespace App\Application\Command\WorkoutSession;

use App\Domain\Repository\WorkoutSessionRepositoryInterface;

final readonly class DeleteWorkoutSessionHandler
{
    public function __construct(
        private WorkoutSessionRepositoryInterface $workoutSessionRepository,
    ) {
    }

    public function handle(DeleteWorkoutSessionCommand $command): void
    {
        $command->workoutSession->delete($command->deletedBy);

        $this->workoutSessionRepository->save($command->workoutSession);
    }
}
