<?php

declare(strict_types=1);

/*
 * This file is part of the proprietary project.
 *
 * This file and its contents are confidential and protected by copyright law.
 * Unauthorized copying, distribution, or disclosure of this content
 * is strictly prohibited without prior written consent from the author or
 * copyright owner.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

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
        // Wybierz nazwę w zależności od języka
        $name = match ($lang) {
            'en' => $exercise->getNameEn() ?? $exercise->getName(), // fallback do PL
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
