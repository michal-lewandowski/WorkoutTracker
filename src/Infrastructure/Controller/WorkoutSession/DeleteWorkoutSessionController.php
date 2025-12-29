<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\WorkoutSession;

use App\Application\Command\WorkoutSession\DeleteWorkoutSessionCommand;
use App\Application\Command\WorkoutSession\DeleteWorkoutSessionHandler;
use App\Domain\Entity\User;
use App\Domain\Exception\WorkoutSessionNotFoundException;
use App\Domain\Repository\WorkoutSessionRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/workout-sessions/{id}', name: 'delete_workout_session', methods: ['DELETE'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class DeleteWorkoutSessionController extends AbstractController
{
    public function __construct(
        private readonly WorkoutSessionRepositoryInterface $workoutSessionRepository,
        private readonly DeleteWorkoutSessionHandler $handler,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $workoutSession = $this->workoutSessionRepository->findById(
            id: $id,
            userId: $user->getId()
        );

        if (null === $workoutSession) {
            throw WorkoutSessionNotFoundException::withId($id);
        }

        $command = new DeleteWorkoutSessionCommand(
            workoutSession: $workoutSession,
            deletedBy: $user
        );

        $this->handler->handle($command);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
