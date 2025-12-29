<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\WorkoutSession;

use App\Domain\Entity\User;
use App\Domain\Exception\WorkoutSessionNotFoundException;
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
        private readonly WorkoutSessionRepositoryInterface $workoutSessionRepository,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $workoutSession = $this->workoutSessionRepository->findByIdWithExercises(
            id: $id,
            userId: $user->getId()
        );

        if (null === $workoutSession) {
            throw WorkoutSessionNotFoundException::withId($id);
        }

        $workoutExerciseDtos = [];
        foreach ($workoutSession->getWorkoutExercises() as $workoutExercise) {
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
