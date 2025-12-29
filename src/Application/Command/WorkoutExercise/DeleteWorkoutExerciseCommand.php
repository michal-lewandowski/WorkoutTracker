<?php

declare(strict_types=1);

namespace App\Application\Command\WorkoutExercise;

final readonly class DeleteWorkoutExerciseCommand
{
    public function __construct(
        public string $userId,
        public string $workoutExerciseId,
    ) {
    }
}
