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

        // Utworzenie commanda
        $command = new DeleteWorkoutExerciseCommand(
            userId: $user->getId(),
            workoutExerciseId: $id
        );

        // Wykonanie commanda
        $this->handler->handle($command);

        // Zwracamy 204 No Content
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
