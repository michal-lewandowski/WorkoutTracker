<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

final readonly class WorkoutExerciseDto
{
    /**
     * @param array<ExerciseSetDto> $exerciseSets
     */
    public function __construct(
        public string $id,
        public string $workoutSessionId,
        public string $exerciseId,
        public ExerciseSummaryDto $exercise,
        public array $exerciseSets,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
