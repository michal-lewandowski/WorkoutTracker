<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\WorkoutSession;

interface WorkoutSessionRepositoryInterface
{
    public function save(WorkoutSession $workoutSession): void;

    public function findById(string $id, ?string $userId = null): ?WorkoutSession;

    /**
     * @return array<WorkoutSession>
     */
    public function findByUserIdPaginated(
        string $userId,
        int $limit,
        int $offset,
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null,
        string $sortBy = 'date',
        string $sortOrder = 'desc',
    ): array;

    public function countByUserId(
        string $userId,
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null,
    ): int;

    public function findByIdWithExercises(string $id, string $userId): ?WorkoutSession;
}
