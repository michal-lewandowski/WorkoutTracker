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

use App\Domain\Entity\ExerciseSet;
use App\Domain\Entity\WorkoutExercise;
use App\Domain\Exception\ExerciseNotFoundException;
use App\Domain\Exception\WorkoutSessionNotFoundException;
use App\Domain\Repository\ExerciseRepositoryInterface;
use App\Domain\Repository\WorkoutExerciseRepositoryInterface;
use App\Domain\Repository\WorkoutSessionRepositoryInterface;

final readonly class CreateWorkoutExerciseHandler
{
    public function __construct(
        private WorkoutSessionRepositoryInterface $workoutSessionRepository,
        private ExerciseRepositoryInterface $exerciseRepository,
        private WorkoutExerciseRepositoryInterface $workoutExerciseRepository,
    ) {
    }

    public function handle(CreateWorkoutExerciseCommand $command): void
    {
        // 1. Pobierz i zwaliduj WorkoutSession
        $workoutSession = $this->workoutSessionRepository->findById(
            $command->workoutSessionId,
            $command->userId
        );

        if (null === $workoutSession) {
            throw WorkoutSessionNotFoundException::withId($command->workoutSessionId);
        }

        // 2. Sprawdź czy sesja nie jest usunięta
        if ($workoutSession->isDeleted()) {
            throw WorkoutSessionNotFoundException::withId($command->workoutSessionId);
        }

        // 3. Pobierz i zwaliduj Exercise
        $exercise = $this->exerciseRepository->findById($command->exerciseId);

        if (null === $exercise) {
            throw ExerciseNotFoundException::withId($command->exerciseId);
        }

        // 4. Utwórz WorkoutExercise
        $workoutExercise = WorkoutExercise::create($command->id, $workoutSession, $exercise);

        // 5. Utwórz ExerciseSets (jeśli są podane)
        if (null !== $command->sets && count($command->sets) > 0) {
            foreach ($command->sets as $setData) {
                // Konwersja kg -> grams
                $weightGrams = (int) round($setData['weightKg'] * 1000);

                $exerciseSet = ExerciseSet::create(
                    workoutExercise: $workoutExercise,
                    setsCount: $setData['setsCount'],
                    reps: $setData['reps'],
                    weightGrams: $weightGrams
                );

                // Doctrine cascade persist zadba o zapisanie
                $workoutExercise->getExerciseSets()->add($exerciseSet);
            }
        }

        // 6. Flush wszystkich zmian
        $this->workoutExerciseRepository->save($workoutExercise);
    }
}
