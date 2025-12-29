<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

final readonly class ExerciseSetDto
{
    public function __construct(
        public string $id,
        public string $workoutExerciseId,
        public int $setsCount,
        public int $reps,
        public float $weightKg,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
