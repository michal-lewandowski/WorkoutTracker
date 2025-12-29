<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\WorkoutSession;

use App\Application\Command\WorkoutSession\UpdateWorkoutSessionCommand;
use App\Application\Command\WorkoutSession\UpdateWorkoutSessionHandler;
use App\Domain\Entity\User;
use App\Domain\Repository\WorkoutSessionRepositoryInterface;
use App\Infrastructure\Api\Input\UpdateWorkoutSessionRequestDto;
use App\Infrastructure\Api\Output\ExerciseSetDto;
use App\Infrastructure\Api\Output\ExerciseSummaryDto;
use App\Infrastructure\Api\Output\WorkoutExerciseDto;
use App\Infrastructure\Api\Output\WorkoutSessionDetailDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/workout-sessions/{id}', name: 'update_workout_session', methods: ['PUT'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class UpdateWorkoutSessionController extends AbstractController
{
    public function __construct(
        private readonly WorkoutSessionRepositoryInterface $workoutSessionRepository,
        private readonly UpdateWorkoutSessionHandler $handler,
    ) {
    }

    public function __invoke(
        string $id,
        #[MapRequestPayload] UpdateWorkoutSessionRequestDto $requestDto,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $date = new \DateTimeImmutable($requestDto->date);

        $command = new UpdateWorkoutSessionCommand(
            id: $id,
            userId: $user->getId(),
            date: $date,
            name: $requestDto->name,
            notes: $requestDto->notes
        );

        $this->handler->handle($command);
        $updatedWorkoutSession = $this->workoutSessionRepository->findById($command->id);

        $workoutExerciseDtos = [];
        foreach ($updatedWorkoutSession->getWorkoutExercises() as $workoutExercise) {
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
                workoutSessionId: $updatedWorkoutSession->getId(),
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
            id: $updatedWorkoutSession->getId(),
            userId: $updatedWorkoutSession->getUser()->getId(),
            date: $updatedWorkoutSession->getDate()->format('Y-m-d'),
            name: $updatedWorkoutSession->getName(),
            notes: $updatedWorkoutSession->getNotes(),
            workoutExercises: $workoutExerciseDtos,
            createdAt: $updatedWorkoutSession->getCreatedAt(),
            updatedAt: $updatedWorkoutSession->getUpdatedAt()
        );

        return $this->json($responseDto, Response::HTTP_OK);
    }
}
