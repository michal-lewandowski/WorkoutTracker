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

use App\Domain\Entity\User;
use App\Domain\Repository\WorkoutSessionRepositoryInterface;
use App\Infrastructure\Api\Input\GetWorkoutSessionsQueryDto;
use App\Infrastructure\Api\Output\WorkoutSessionDto;
use App\Infrastructure\Api\Output\WorkoutSessionListDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/workout-sessions', name: 'get_workout_sessions', methods: ['GET'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class GetWorkoutSessionsController extends AbstractController
{
    public function __construct(
        private readonly WorkoutSessionRepositoryInterface $workoutSessionRepository,
    ) {
    }

    public function __invoke(
        #[MapQueryString] GetWorkoutSessionsQueryDto $queryDto,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        // Konwersja dat z string na DateTimeImmutable jeśli są ustawione
        $dateFrom = null !== $queryDto->dateFrom
            ? new \DateTimeImmutable($queryDto->dateFrom)
            : null;

        $dateTo = null !== $queryDto->dateTo
            ? new \DateTimeImmutable($queryDto->dateTo)
            : null;

        // Pobranie sesji treningowych z paginacją i filtrowaniem
        $workoutSessions = $this->workoutSessionRepository->findByUserIdPaginated(
            userId: $user->getId(),
            limit: $queryDto->limit,
            offset: $queryDto->offset,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            sortBy: $queryDto->sortBy,
            sortOrder: $queryDto->sortOrder
        );

        // Pobranie łącznej liczby rekordów
        $total = $this->workoutSessionRepository->countByUserId(
            userId: $user->getId(),
            dateFrom: $dateFrom,
            dateTo: $dateTo
        );

        // Mapowanie encji na DTOs
        $items = array_map(
            fn ($session) => new WorkoutSessionDto(
                id: $session->getId(),
                date: $session->getDate()->format('Y-m-d'),
                name: $session->getName(),
                notes: $session->getNotes(),
                createdAt: $session->getCreatedAt(),
                exerciseCount: $session->getWorkoutExercises()->count()
            ),
            $workoutSessions
        );

        $responseDto = new WorkoutSessionListDto(
            items: $items,
            total: $total,
            limit: $queryDto->limit,
            offset: $queryDto->offset
        );

        return $this->json($responseDto, Response::HTTP_OK);
    }
}
