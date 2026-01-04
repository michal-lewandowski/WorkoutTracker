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

    /**
     * Find all exercises that user has performed at least once in workout sessions.
     * Returns array of arrays with structure: ['exercise' => Exercise, 'workoutsCount' => int].
     *
     * @return array<int, array{exercise: Exercise, workoutsCount: int}>
     */
    public function findDoneExercisesByUser(
        string $userId,
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null,
        string $sortBy = 'name',
        string $sortOrder = 'asc',
    ): array;
}
