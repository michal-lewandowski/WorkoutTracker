<?php

declare(strict_types=1);

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
        $workoutSession = $this->workoutSessionRepository->findById(
            $command->workoutSessionId,
            $command->userId
        );

        if (null === $workoutSession) {
            throw WorkoutSessionNotFoundException::withId($command->workoutSessionId);
        }

        if ($workoutSession->isDeleted()) {
            throw WorkoutSessionNotFoundException::withId($command->workoutSessionId);
        }

        $exercise = $this->exerciseRepository->findById($command->exerciseId);

        if (null === $exercise) {
            throw ExerciseNotFoundException::withId($command->exerciseId);
        }

        $workoutExercise = WorkoutExercise::create($command->id, $workoutSession, $exercise);

        if (null !== $command->sets && count($command->sets) > 0) {
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
        }

        $this->workoutExerciseRepository->save($workoutExercise);
    }
}
