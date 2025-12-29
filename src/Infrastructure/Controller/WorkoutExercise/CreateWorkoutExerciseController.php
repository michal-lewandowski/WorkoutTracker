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

use App\Application\Command\WorkoutExercise\CreateWorkoutExerciseCommand;
use App\Application\Command\WorkoutExercise\CreateWorkoutExerciseHandler;
use App\Domain\Entity\User;
use App\Domain\Repository\WorkoutExerciseRepositoryInterface;
use App\Infrastructure\Api\Input\CreateWorkoutExerciseRequestDto;
use App\Infrastructure\Api\Output\ExerciseSetDto;
use App\Infrastructure\Api\Output\ExerciseSummaryDto;
use App\Infrastructure\Api\Output\WorkoutExerciseDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1/workout-exercises', name: 'create_workout_exercise', methods: ['POST'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CreateWorkoutExerciseController extends AbstractController
{
    public function __construct(
        private readonly CreateWorkoutExerciseHandler $handler,
        private readonly WorkoutExerciseRepositoryInterface $workoutExerciseRepository,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] CreateWorkoutExerciseRequestDto $requestDto,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        // Przygotowanie danych sets (konwersja DTO do array)
        $sets = null;
        if (null !== $requestDto->sets) {
            $sets = array_map(
                fn ($setDto) => [
                    'setsCount' => $setDto->setsCount,
                    'reps' => $setDto->reps,
                    'weightKg' => $setDto->weightKg,
                ],
                $requestDto->sets
            );
        }

        // Utworzenie commanda
        $command = new CreateWorkoutExerciseCommand(
            id: Uuid::v4(),
            userId: $user->getId(),
            workoutSessionId: $requestDto->workoutSessionId,
            exerciseId: $requestDto->exerciseId,
            sets: $sets
        );

        // Wykonanie commanda
        $this->handler->handle($command);

        $workoutExercise = $this->workoutExerciseRepository->findById((string) $command->id);

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

        return $this->json($responseDto, Response::HTTP_CREATED);
    }
}
