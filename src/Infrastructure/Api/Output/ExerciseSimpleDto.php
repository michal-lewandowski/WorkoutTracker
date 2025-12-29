<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

use App\Domain\Entity\Exercise;

final readonly class ExerciseSimpleDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $nameEn,
    ) {
    }

    public static function fromEntity(Exercise $exercise): self
    {
        return new self(
            id: $exercise->getId(),
            name: $exercise->getName(),
            nameEn: $exercise->getNameEn(),
        );
    }
}
