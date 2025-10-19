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

use App\Application\Exception\WorkoutExerciseNotFoundException;
use App\Domain\Entity\ExerciseSet;
use App\Domain\Entity\WorkoutExercise;
use App\Domain\Repository\WorkoutExerciseRepositoryInterface;

final readonly class UpdateWorkoutExerciseHandler
{
    public function __construct(
        private WorkoutExerciseRepositoryInterface $workoutExerciseRepository,
    ) {}

    public function handle(UpdateWorkoutExerciseCommand $command): WorkoutExercise
    {
        // 1. Pobierz i zwaliduj WorkoutExercise z filtrowaniem po userId
        $workoutExercise = $this->workoutExerciseRepository->findById(
            $command->workoutExerciseId,
            $command->userId
        );

        if (null === $workoutExercise) {
            throw WorkoutExerciseNotFoundException::withId($command->workoutExerciseId);
        }

        // 2. Usuń wszystkie istniejące ExerciseSets
        // orphanRemoval=true w relacji automatycznie usunie ExerciseSets z bazy
        $workoutExercise->getExerciseSets()->clear();

        // 3. Utwórz nowe ExerciseSets
        foreach ($command->sets as $setData) {
            // Konwersja kg -> grams
            $weightGrams = (int) round($setData['weightKg'] * 1000);

            $exerciseSet = ExerciseSet::create(
                workoutExercise: $workoutExercise,
                setsCount: $setData['setsCount'],
                reps: $setData['reps'],
                weightGrams: $weightGrams
            );

            // Dodaj do kolekcji - Doctrine cascade persist zadba o zapisanie
            $workoutExercise->getExerciseSets()->add($exerciseSet);
        }

        // 4. Flush zmian (usunie stare sety przez orphanRemoval i zapisze nowe)
        $this->workoutExerciseRepository->flush();

        return $workoutExercise;
    }
}

