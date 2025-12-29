<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Exercise;

interface ExerciseRepositoryInterface
{
    /**
     * @return list<Exercise>
     */
    public function findAll(): array;

    public function findById(string $id): ?Exercise;

    /**
     * @return list<Exercise>
     */
    public function findByFilters(?string $muscleCategoryId, ?string $search): array;
}
