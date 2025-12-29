<?php

declare(strict_types=1);

namespace App\Application\Command\WorkoutExercise;

use App\Domain\Entity\ExerciseSet;
use App\Domain\Exception\WorkoutExerciseNotFoundException;
use App\Domain\Repository\WorkoutExerciseRepositoryInterface;

final readonly class UpdateWorkoutExerciseHandler
{
    public function __construct(
        private WorkoutExerciseRepositoryInterface $workoutExerciseRepository,
    ) {
    }

    public function handle(UpdateWorkoutExerciseCommand $command): void
    {
        $workoutExercise = $this->workoutExerciseRepository->findById(
            $command->workoutExerciseId,
            $command->userId
        );

        if (null === $workoutExercise) {
            throw WorkoutExerciseNotFoundException::withId($command->workoutExerciseId);
        }

        $workoutExercise->getExerciseSets()->clear();

        foreach ($command->sets as $setData) {
            $weightGrams = (int) round($setData['weightKg'] * 1000);

            $exerciseSet = ExerciseSet::create(
                workoutExercise: $workoutExercise,
                setsCount: $setData['setsCount'],
                reps: $setData['reps'],
                weightGrams: $weightGrams
            );

            $workoutExercise->getExerciseSets()->add($exerciseSet);
        }

        $this->workoutExerciseRepository->save($workoutExercise);
    }
}
