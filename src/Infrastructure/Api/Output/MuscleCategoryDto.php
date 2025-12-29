<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

use App\Domain\Entity\MuscleCategory;

final readonly class MuscleCategoryDto
{
    public function __construct(
        public string $id,
        public string $namePl,
        public string $nameEn,
        public \DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromEntity(MuscleCategory $muscleCategory): self
    {
        return new self(
            id: $muscleCategory->getId(),
            namePl: $muscleCategory->getNamePl(),
            nameEn: $muscleCategory->getNameEn(),
            createdAt: $muscleCategory->getCreatedAt(),
        );
    }
}
