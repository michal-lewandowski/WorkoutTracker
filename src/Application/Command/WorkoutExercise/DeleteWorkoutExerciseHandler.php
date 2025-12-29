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

use App\Domain\Exception\WorkoutExerciseNotFoundException;
use App\Domain\Repository\WorkoutExerciseRepositoryInterface;

final readonly class DeleteWorkoutExerciseHandler
{
    public function __construct(
        private WorkoutExerciseRepositoryInterface $workoutExerciseRepository,
    ) {
    }

    public function handle(DeleteWorkoutExerciseCommand $command): void
    {
        // 1. Pobierz i zwaliduj WorkoutExercise z filtrowaniem po userId
        $workoutExercise = $this->workoutExerciseRepository->findById(
            $command->workoutExerciseId,
            $command->userId
        );

        if (null === $workoutExercise) {
            throw WorkoutExerciseNotFoundException::withId($command->workoutExerciseId);
        }

        // 2. Usuń WorkoutExercise (Doctrine cascade="remove" usunie też ExerciseSets)
        $this->workoutExerciseRepository->delete($workoutExercise);

        // 3. Flush zmian
        $this->workoutExerciseRepository->flush();
    }
}
