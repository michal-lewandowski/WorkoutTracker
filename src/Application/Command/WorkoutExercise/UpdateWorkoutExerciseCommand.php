<?php

declare(strict_types=1);

namespace App\Application\Command\WorkoutExercise;

final readonly class UpdateWorkoutExerciseCommand
{
    /**
     * @param array<array{setsCount: int, reps: int, weightKg: float}> $sets
     */
    public function __construct(
        public string $userId,
        public string $workoutExerciseId,
        public array $sets,
    ) {
    }
}
