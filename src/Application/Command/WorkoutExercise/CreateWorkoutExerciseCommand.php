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

namespace App\Application\Command\WorkoutExercise;

use Symfony\Component\Uid\Uuid;

final readonly class CreateWorkoutExerciseCommand
{
    /**
     * @param array<array{setsCount: int, reps: int, weightKg: float}>|null $sets
     */
    public function __construct(
        public Uuid $id,
        public string $userId,
        public string $workoutSessionId,
        public string $exerciseId,
        public ?array $sets = null,
    ) {
    }
}
