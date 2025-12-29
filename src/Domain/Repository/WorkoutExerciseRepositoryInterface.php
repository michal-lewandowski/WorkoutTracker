<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\WorkoutExercise;

interface WorkoutExerciseRepositoryInterface
{
    public function save(WorkoutExercise $workoutExercise): void;

    public function findById(string $id, ?string $userId = null): ?WorkoutExercise;

    public function delete(WorkoutExercise $workoutExercise): void;

    public function flush(): void;

    /**
     * @return array<array{date: \DateTimeImmutable, sessionId: string, maxWeightKg: float}>
     */
    public function findMaxWeightPerSessionByExerciseAndUser(
        string $exerciseId,
        string $userId,
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null,
        ?int $limit = 100,
    ): array;
}
