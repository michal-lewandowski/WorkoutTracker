<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

use App\Domain\Entity\Exercise;

final readonly class DoneExerciseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public int $workoutsCount,
        public MuscleCategoryDto $muscleCategory,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function fromEntityWithCount(Exercise $exercise, int $workoutsCount): self
    {
        return new self(
            id: $exercise->getId(),
            name: $exercise->getName(),
            workoutsCount: $workoutsCount,
            muscleCategory: MuscleCategoryDto::fromEntity($exercise->getMuscleCategory()),
            createdAt: $exercise->getCreatedAt(),
            updatedAt: $exercise->getUpdatedAt(),
        );
    }
}
