<?php

declare(strict_types=1);

namespace App\Application\Command\WorkoutExercise;

use App\Domain\Exception\WorkoutExerciseNotFoundException;
use App\Domain\Repository\WorkoutExerciseRepositoryInterface;

final readonly class DeleteWorkoutExerciseHandler
{
    public function __construct(
        private WorkoutExerciseRepositoryInterface $workoutExerciseRepository,
    ) {
    }

    public function handle(DeleteWorkoutExerciseCommand $command): void
    {
        $workoutExercise = $this->workoutExerciseRepository->findById(
            $command->workoutExerciseId,
            $command->userId
        );

        if (null === $workoutExercise) {
            throw WorkoutExerciseNotFoundException::withId($command->workoutExerciseId);
        }

        $this->workoutExerciseRepository->delete($workoutExercise);

        $this->workoutExerciseRepository->flush();
    }
}
