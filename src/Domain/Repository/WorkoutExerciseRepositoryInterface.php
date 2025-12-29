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

namespace App\Domain\Repository;

use App\Domain\Entity\WorkoutExercise;

interface WorkoutExerciseRepositoryInterface
{
    public function save(WorkoutExercise $workoutExercise): void;

    public function findById(string $id, ?string $userId = null): ?WorkoutExercise;

    public function delete(WorkoutExercise $workoutExercise): void;

    public function flush(): void;

    /**
     * Znajduje maksymalną wagę dla danego ćwiczenia w każdej sesji treningowej użytkownika.
     *
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
