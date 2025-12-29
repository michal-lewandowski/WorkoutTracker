<?php

declare(strict_types=1);

namespace App\Application\Command\WorkoutExercise;

use Symfony\Component\Uid\Uuid;

final readonly class CreateWorkoutExerciseCommand
{
    /**
     * @param array<array{setsCount: int, reps: int, weightKg: float}>|null $sets
     */
    public function __construct(
        public Uuid $id,
        public string $userId,
        public string $workoutSessionId,
        public string $exerciseId,
        public ?array $sets = null,
    ) {
    }
}
