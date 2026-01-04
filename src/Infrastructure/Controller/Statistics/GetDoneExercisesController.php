<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Statistics;

use App\Application\Query\Statistics\GetDoneExercisesQuery;
use App\Application\Query\Statistics\GetDoneExercisesQueryHandler;
use App\Domain\Entity\User;
use App\Infrastructure\Api\Input\GetDoneExercisesQueryDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/statistics/done-exercises', name: 'get_done_exercises', methods: ['GET'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class GetDoneExercisesController extends AbstractController
{
    public function __construct(
        private readonly GetDoneExercisesQueryHandler $handler,
    ) {
    }

    public function __invoke(
        #[MapQueryString] ?GetDoneExercisesQueryDto $queryDto = null,
    ): JsonResponse {
        $queryDto ??= new GetDoneExercisesQueryDto();

        /** @var User $user */
        $user = $this->getUser();

        try {
            $dateFrom = null !== $queryDto->dateFrom
                ? new \DateTimeImmutable($queryDto->dateFrom)
                : null;

            $dateTo = null !== $queryDto->dateTo
                ? new \DateTimeImmutable($queryDto->dateTo)
                : null;

            $query = new GetDoneExercisesQuery(
                userId: $user->getId(),
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                sortBy: $queryDto->sortBy ?? 'workoutsCount',
                sortOrder: $queryDto->sortOrder ?? 'asc',
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'code' => Response::HTTP_BAD_REQUEST,
            ], Response::HTTP_BAD_REQUEST);
        }

        $exercises = $this->handler->handle($query);

        return $this->json($exercises, Response::HTTP_OK);
    }
}
