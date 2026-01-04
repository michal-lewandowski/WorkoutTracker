<?php

declare(strict_types=1);

namespace App\Application\Query\Statistics;

use App\Domain\Repository\ExerciseRepositoryInterface;
use App\Infrastructure\Api\Output\DoneExerciseDto;

final readonly class GetDoneExercisesQueryHandler
{
    public function __construct(
        private ExerciseRepositoryInterface $exerciseRepository,
    ) {
    }

    /**
     * @return DoneExerciseDto[]
     */
    public function handle(GetDoneExercisesQuery $query): array
    {
        $exercisesWithCount = $this->exerciseRepository->findDoneExercisesByUser(
            userId: $query->userId,
            dateFrom: $query->dateFrom,
            dateTo: $query->dateTo,
            sortBy: $query->sortBy,
            sortOrder: $query->sortOrder,
        );

        return array_map(
            fn (array $item) => DoneExerciseDto::fromEntityWithCount(
                exercise: $item['exercise'],
                workoutsCount: $item['workoutsCount']
            ),
            $exercisesWithCount
        );
    }
}
