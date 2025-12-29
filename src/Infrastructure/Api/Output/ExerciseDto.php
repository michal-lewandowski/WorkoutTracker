<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

use App\Domain\Entity\Exercise;

final readonly class ExerciseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public MuscleCategoryDto $muscleCategory,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function fromEntity(Exercise $exercise, string $lang = 'pl'): self
    {
        $name = match ($lang) {
            'en' => $exercise->getNameEn() ?? $exercise->getName(),
            default => $exercise->getName(),
        };

        return new self(
            id: $exercise->getId(),
            name: $name,
            muscleCategory: MuscleCategoryDto::fromEntity($exercise->getMuscleCategory()),
            createdAt: $exercise->getCreatedAt(),
            updatedAt: $exercise->getUpdatedAt(),
        );
    }
}
