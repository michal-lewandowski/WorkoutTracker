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

namespace App\Application\Command\WorkoutSession;

use App\Domain\Exception\WorkoutSessionNotFoundException;
use App\Domain\Repository\WorkoutSessionRepositoryInterface;

final readonly class UpdateWorkoutSessionHandler
{
    public function __construct(
        private WorkoutSessionRepositoryInterface $workoutSessionRepository,
    ) {
    }

    public function handle(UpdateWorkoutSessionCommand $command): void
    {
        // Pobranie sesji z weryfikacją dostępu
        $workoutSession = $this->workoutSessionRepository->findByIdWithExercises(
            id: $command->id,
            userId: $command->userId
        );

        if (null === $workoutSession) {
            throw WorkoutSessionNotFoundException::withId($command->id);
        }

        $workoutSession->update(
            date: $command->date,
            name: $command->name,
            notes: $command->notes
        );

        $this->workoutSessionRepository->save($workoutSession);
    }
}
