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

        // Przekształć dane z command na format ułatwiający porównanie
        $newSets = [];
        foreach ($command->sets as $setData) {
            $newSets[] = [
                'setsCount' => $setData['setsCount'],
                'reps' => $setData['reps'],
                'weightGrams' => (int) round($setData['weightKg'] * 1000),
            ];
        }

        // Przejdź przez istniejące sety i usuń te, które nie pasują do nowych danych
        $existingSets = $workoutExercise->getExerciseSets();

        foreach ($existingSets as $existingSet) {
            $matches = false;

            foreach ($newSets as $key => $newSetData) {
                if (
                    $existingSet->getSetsCount() === $newSetData['setsCount']
                    && $existingSet->getReps() === $newSetData['reps']
                    && $existingSet->getWeightGrams() === $newSetData['weightGrams']
                ) {
                    $matches = true;
                    // Usuń z tablicy newSets, aby nie dodać go ponownie
                    unset($newSets[$key]);
                    break;
                }
            }

            // Jeśli set nie pasuje do żadnego z nowych, usuń go
            if (!$matches) {
                $existingSets->removeElement($existingSet);
            }
        }

        // Dodaj nowe sety (te które pozostały w $newSets po usunięciu dopasowanych)
        foreach ($newSets as $newSetData) {
            $exerciseSet = ExerciseSet::create(
                workoutExercise: $workoutExercise,
                setsCount: $newSetData['setsCount'],
                reps: $newSetData['reps'],
                weightGrams: $newSetData['weightGrams']
            );

            $workoutExercise->getExerciseSets()->add($exerciseSet);
        }

        $this->workoutExerciseRepository->save($workoutExercise);
    }
}
