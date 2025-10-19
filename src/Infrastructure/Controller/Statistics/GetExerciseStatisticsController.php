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

namespace App\Infrastructure\Controller\Statistics;

use App\Application\Command\Statistics\GetExerciseStatisticsCommand;
use App\Application\Command\Statistics\GetExerciseStatisticsHandler;
use App\Domain\Entity\User;
use App\Infrastructure\Api\Input\GetExerciseStatisticsQueryDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1/statistics/exercise/{exerciseId}', name: 'get_exercise_statistics', methods: ['GET'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class GetExerciseStatisticsController extends AbstractController
{
    public function __construct(
        private readonly GetExerciseStatisticsHandler $handler,
    ) {
    }

    public function __invoke(
        string $exerciseId,
        #[MapQueryString] ?GetExerciseStatisticsQueryDto $queryDto = null,
    ): JsonResponse {
        // Walidacja UUID exerciseId
        if (!Uuid::isValid($exerciseId)) {
            return $this->json([
                'error' => 'Invalid exercise ID format',
                'code' => 400,
            ], Response::HTTP_BAD_REQUEST);
        }

        // Default values if no query params provided
        $queryDto ??= new GetExerciseStatisticsQueryDto();

        /** @var User $user */
        $user = $this->getUser();

        // Konwersja dat z string na DateTimeImmutable jeśli są ustawione
        $dateFrom = null !== $queryDto->dateFrom
            ? new \DateTimeImmutable($queryDto->dateFrom)
            : null;

        $dateTo = null !== $queryDto->dateTo
            ? new \DateTimeImmutable($queryDto->dateTo)
            : null;

        // Utworzenie komendy
        $command = new GetExerciseStatisticsCommand(
            exerciseId: $exerciseId,
            userId: $user->getId(),
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            limit: $queryDto->limit,
        );

        // Wykonanie komendy
        $statistics = $this->handler->handle($command);

        return $this->json($statistics, Response::HTTP_OK);
    }
}

