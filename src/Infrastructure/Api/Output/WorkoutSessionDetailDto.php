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

final readonly class WorkoutSessionDetailDto
{
    /**
     * @param array<WorkoutExerciseDto> $workoutExercises
     */
    public function __construct(
        public string $id,
        public string $userId,
        public string $date,
        public ?string $name,
        public ?string $notes,
        public array $workoutExercises,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
    }
}
