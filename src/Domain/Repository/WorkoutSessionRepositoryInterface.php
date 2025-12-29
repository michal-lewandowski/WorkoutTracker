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

    /**
     * Pobiera sesję treningową wraz z wszystkimi powiązanymi ćwiczeniami i seriami.
     * Używa JOIN FETCH aby uniknąć problemu N+1.
     *
     * @param string $id     ID sesji treningowej
     * @param string $userId ID użytkownika (weryfikacja dostępu)
     */
    public function findByIdWithExercises(string $id, string $userId): ?WorkoutSession;
}
