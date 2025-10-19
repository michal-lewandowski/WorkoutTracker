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

use App\Domain\Entity\WorkoutSession;
use App\Domain\Repository\WorkoutSessionRepositoryInterface;

final readonly class CreateWorkoutSessionHandler
{
    public function __construct(
        private WorkoutSessionRepositoryInterface $workoutSessionRepository
    ) {
    }

    public function handle(CreateWorkoutSessionCommand $command): WorkoutSession
    {
        $workoutSession = WorkoutSession::create(
            user: $command->user,
            date: $command->date,
            name: $command->name,
            notes: $command->notes
        );

        $this->workoutSessionRepository->save($workoutSession);

        return $workoutSession;
    }
}

