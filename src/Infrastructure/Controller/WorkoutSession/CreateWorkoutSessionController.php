<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\WorkoutSession;

use App\Application\Command\WorkoutSession\CreateWorkoutSessionCommand;
use App\Application\Command\WorkoutSession\CreateWorkoutSessionHandler;
use App\Domain\Entity\User;
use App\Domain\Repository\WorkoutSessionRepositoryInterface;
use App\Infrastructure\Api\Input\CreateWorkoutSessionRequestDto;
use App\Infrastructure\Api\Output\WorkoutSessionDetailDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1/workout-sessions', name: 'create_workout_session', methods: ['POST'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CreateWorkoutSessionController extends AbstractController
{
    public function __construct(
        private readonly WorkoutSessionRepositoryInterface $workoutSessionRepository,
        private readonly CreateWorkoutSessionHandler $handler,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] CreateWorkoutSessionRequestDto $requestDto,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $date = new \DateTimeImmutable($requestDto->date);

        $command = new CreateWorkoutSessionCommand(
            id: Uuid::v4(),
            user: $user,
            date: $date,
            name: $requestDto->name,
            notes: $requestDto->notes
        );

        $this->handler->handle($command);
        $workoutSession = $this->workoutSessionRepository->findById((string) $command->id);

        $responseDto = new WorkoutSessionDetailDto(
            id: $workoutSession->getId(),
            userId: $workoutSession->getUser()->getId(),
            date: $workoutSession->getDate()->format('Y-m-d'),
            name: $workoutSession->getName(),
            notes: $workoutSession->getNotes(),
            workoutExercises: [],
            createdAt: $workoutSession->getCreatedAt(),
            updatedAt: $workoutSession->getUpdatedAt()
        );

        return $this->json($responseDto, Response::HTTP_CREATED);
    }
}
