<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

final readonly class WorkoutSessionDetailDto
{
    /**
     * @param array<WorkoutExerciseDto> $workoutExercises
     */
    public function __construct(
        public string $id,
        public string $userId,
        public string $date,
        public ?string $name,
        public ?string $notes,
        public array $workoutExercises,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
    }
}
