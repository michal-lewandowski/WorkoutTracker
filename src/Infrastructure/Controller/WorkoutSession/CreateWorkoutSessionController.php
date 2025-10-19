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

use App\Application\Command\WorkoutSession\CreateWorkoutSessionCommand;
use App\Application\Command\WorkoutSession\CreateWorkoutSessionHandler;
use App\Domain\Entity\User;
use App\Infrastructure\Api\Input\CreateWorkoutSessionRequestDto;
use App\Infrastructure\Api\Output\WorkoutSessionDetailDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/workout-sessions', name: 'create_workout_session', methods: ['POST'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CreateWorkoutSessionController extends AbstractController
{
    public function __construct(
        private readonly CreateWorkoutSessionHandler $handler
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] CreateWorkoutSessionRequestDto $requestDto
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        // Konwersja daty z string na DateTimeImmutable
        $date = new \DateTimeImmutable($requestDto->date);

        // Utworzenie commanda
        $command = new CreateWorkoutSessionCommand(
            user: $user,
            date: $date,
            name: $requestDto->name,
            notes: $requestDto->notes
        );

        // Wykonanie commanda
        $workoutSession = $this->handler->handle($command);

        // Mapowanie encji na DTO
        $responseDto = new WorkoutSessionDetailDto(
            id: $workoutSession->getId(),
            userId: $workoutSession->getUser()->getId(),
            date: $workoutSession->getDate()->format('Y-m-d'),
            name: $workoutSession->getName(),
            notes: $workoutSession->getNotes(),
            workoutExercises: [], // Nowa sesja nie ma jeszcze ćwiczeń
            createdAt: $workoutSession->getCreatedAt(),
            updatedAt: $workoutSession->getUpdatedAt()
        );

        return $this->json($responseDto, Response::HTTP_CREATED);
    }
}

