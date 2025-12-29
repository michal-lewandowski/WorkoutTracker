<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Statistics;

use App\Application\Query\Statistics\GetExerciseStatisticsQuery;
use App\Application\Query\Statistics\GetExerciseStatisticsQueryHandler;
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
        private readonly GetExerciseStatisticsQueryHandler $handler,
    ) {
    }

    public function __invoke(
        string $exerciseId,
        #[MapQueryString] ?GetExerciseStatisticsQueryDto $queryDto = null,
    ): JsonResponse {
        if (!Uuid::isValid($exerciseId)) {
            return $this->json([
                'error' => 'Invalid exercise ID format',
                'code' => 400,
            ], Response::HTTP_BAD_REQUEST);
        }

        $queryDto ??= new GetExerciseStatisticsQueryDto();

        /** @var User $user */
        $user = $this->getUser();

        $dateFrom = null !== $queryDto->dateFrom
            ? new \DateTimeImmutable($queryDto->dateFrom)
            : null;

        $dateTo = null !== $queryDto->dateTo
            ? new \DateTimeImmutable($queryDto->dateTo)
            : null;

        $command = new GetExerciseStatisticsQuery(
            exerciseId: $exerciseId,
            userId: $user->getId(),
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            limit: $queryDto->limit,
        );

        $statistics = $this->handler->handle($command);

        return $this->json($statistics, Response::HTTP_OK);
    }
}
