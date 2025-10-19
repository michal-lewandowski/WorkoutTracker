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

namespace App\Infrastructure\Controller\WorkoutSession;

use App\Application\Exception\WorkoutSessionNotFoundException;
use App\Domain\Entity\User;
use App\Domain\Repository\WorkoutSessionRepositoryInterface;
use App\Infrastructure\Api\Output\ExerciseSetDto;
use App\Infrastructure\Api\Output\ExerciseSummaryDto;
use App\Infrastructure\Api\Output\WorkoutExerciseDto;
use App\Infrastructure\Api\Output\WorkoutSessionDetailDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/workout-sessions/{id}', name: 'get_workout_session', methods: ['GET'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class GetWorkoutSessionController extends AbstractController
{
    public function __construct(
        private readonly WorkoutSessionRepositoryInterface $workoutSessionRepository
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Pobranie sesji z wszystkimi powiązanymi danymi
        $workoutSession = $this->workoutSessionRepository->findByIdWithExercises(
            id: $id,
            userId: $user->getId()
        );

        if (null === $workoutSession) {
            throw new WorkoutSessionNotFoundException($id);
        }

        // Mapowanie WorkoutExercises na DTOs
        $workoutExerciseDtos = [];
        foreach ($workoutSession->getWorkoutExercises() as $workoutExercise) {
            // Mapowanie ExerciseSets na DTOs
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

            $workoutExerciseDtos[] = new WorkoutExerciseDto(
                id: $workoutExercise->getId(),
                workoutSessionId: $workoutSession->getId(),
                exerciseId: $workoutExercise->getExercise()->getId(),
                exercise: new ExerciseSummaryDto(
                    id: $workoutExercise->getExercise()->getId(),
                    name: $workoutExercise->getExercise()->getName(),
                    nameEn: $workoutExercise->getExercise()->getNameEn(),
                    muscleCategoryId: $workoutExercise->getExercise()->getMuscleCategory()->getId()
                ),
                exerciseSets: $exerciseSetDtos,
                createdAt: $workoutExercise->getCreatedAt()
            );
        }

        $responseDto = new WorkoutSessionDetailDto(
            id: $workoutSession->getId(),
            userId: $workoutSession->getUser()->getId(),
            date: $workoutSession->getDate()->format('Y-m-d'),
            name: $workoutSession->getName(),
            notes: $workoutSession->getNotes(),
            workoutExercises: $workoutExerciseDtos,
            createdAt: $workoutSession->getCreatedAt(),
            updatedAt: $workoutSession->getUpdatedAt()
        );

        return $this->json($responseDto, Response::HTTP_OK);
    }
}

