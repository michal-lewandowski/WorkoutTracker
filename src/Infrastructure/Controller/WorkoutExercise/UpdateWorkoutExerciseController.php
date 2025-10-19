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

namespace App\Infrastructure\Controller\WorkoutExercise;

use App\Application\Command\WorkoutExercise\UpdateWorkoutExerciseCommand;
use App\Application\Command\WorkoutExercise\UpdateWorkoutExerciseHandler;
use App\Domain\Entity\User;
use App\Infrastructure\Api\Input\UpdateWorkoutExerciseRequestDto;
use App\Infrastructure\Api\Output\ExerciseSetDto;
use App\Infrastructure\Api\Output\ExerciseSummaryDto;
use App\Infrastructure\Api\Output\WorkoutExerciseDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/workout-exercises/{id}', name: 'update_workout_exercise', methods: ['PUT'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class UpdateWorkoutExerciseController extends AbstractController
{
    public function __construct(
        private readonly UpdateWorkoutExerciseHandler $handler
    ) {}

    public function __invoke(
        string $id,
        #[MapRequestPayload] UpdateWorkoutExerciseRequestDto $requestDto
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        // Przygotowanie danych sets (konwersja DTO do array)
        $sets = array_map(
            fn ($setDto) => [
                'setsCount' => $setDto->setsCount,
                'reps' => $setDto->reps,
                'weightKg' => $setDto->weightKg,
            ],
            $requestDto->sets
        );

        // Utworzenie commanda
        $command = new UpdateWorkoutExerciseCommand(
            userId: $user->getId(),
            workoutExerciseId: $id,
            sets: $sets
        );

        // Wykonanie commanda
        $workoutExercise = $this->handler->handle($command);

        // Mapowanie encji na DTO
        $exercise = $workoutExercise->getExercise();
        $exerciseSummaryDto = new ExerciseSummaryDto(
            id: $exercise->getId(),
            name: $exercise->getName(),
            nameEn: $exercise->getNameEn(),
            muscleCategoryId: $exercise->getMuscleCategory()->getId()
        );

        $exerciseSetDtos = [];
        foreach ($workoutExercise->getExerciseSets() as $exerciseSet) {
            $exerciseSetDtos[] = new ExerciseSetDto(
                id: $exerciseSet->getId(),
                workoutExerciseId: $workoutExercise->getId(),
                setsCount: $exerciseSet->getSetsCount(),
                reps: $exerciseSet->getReps(),
                weightKg: $exerciseSet->getWeightKg(),
                createdAt: $exerciseSet->getCreatedAt()
            );
        }

        $responseDto = new WorkoutExerciseDto(
            id: $workoutExercise->getId(),
            workoutSessionId: $workoutExercise->getWorkoutSession()->getId(),
            exerciseId: $exercise->getId(),
            exercise: $exerciseSummaryDto,
            exerciseSets: $exerciseSetDtos,
            createdAt: $workoutExercise->getCreatedAt()
        );

        return $this->json($responseDto, Response::HTTP_OK);
    }
}

