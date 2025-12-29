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

        // Pobranie sesji z weryfikacją dostępu
        $workoutSession = $this->workoutSessionRepository->findById(
            id: $id,
            userId: $user->getId()
        );

        if (null === $workoutSession) {
            throw WorkoutSessionNotFoundException::withId($id);
        }

        // Utworzenie i wykonanie commanda
        $command = new DeleteWorkoutSessionCommand(
            workoutSession: $workoutSession,
            deletedBy: $user
        );

        $this->handler->handle($command);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
