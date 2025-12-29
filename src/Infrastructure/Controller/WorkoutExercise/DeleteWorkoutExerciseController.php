<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\WorkoutExercise;

use App\Application\Command\WorkoutExercise\DeleteWorkoutExerciseCommand;
use App\Application\Command\WorkoutExercise\DeleteWorkoutExerciseHandler;
use App\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/workout-exercises/{id}', name: 'delete_workout_exercise', methods: ['DELETE'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class DeleteWorkoutExerciseController extends AbstractController
{
    public function __construct(
        private readonly DeleteWorkoutExerciseHandler $handler,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $command = new DeleteWorkoutExerciseCommand(
            userId: $user->getId(),
            workoutExerciseId: $id
        );

        $this->handler->handle($command);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
