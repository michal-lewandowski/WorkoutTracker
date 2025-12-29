<?php

declare(strict_types=1);

namespace App\Application\Query\Statistics;

use App\Domain\Exception\ExerciseNotFoundException;
use App\Domain\Repository\ExerciseRepositoryInterface;
use App\Domain\Repository\WorkoutExerciseRepositoryInterface;
use App\Domain\Service\StatisticsCalculator;
use App\Infrastructure\Api\Output\ExerciseSimpleDto;
use App\Infrastructure\Api\Output\ExerciseStatisticsDataPointDto;
use App\Infrastructure\Api\Output\ExerciseStatisticsDto;
use App\Infrastructure\Api\Output\ExerciseStatisticsSummaryDto;

final readonly class GetExerciseStatisticsQueryHandler
{
    public function __construct(
        private ExerciseRepositoryInterface $exerciseRepository,
        private WorkoutExerciseRepositoryInterface $workoutExerciseRepository,
        private StatisticsCalculator $statisticsCalculator,
    ) {
    }

    public function handle(GetExerciseStatisticsQuery $command): ExerciseStatisticsDto
    {
        $exercise = $this->exerciseRepository->findById($command->exerciseId);
        if (null === $exercise) {
            throw new ExerciseNotFoundException(sprintf('Exercise with ID "%s" not found', $command->exerciseId));
        }

        $rawDataPoints = $this->workoutExerciseRepository->findMaxWeightPerSessionByExerciseAndUser(
            exerciseId: $command->exerciseId,
            userId: $command->userId,
            dateFrom: $command->dateFrom,
            dateTo: $command->dateTo,
            limit: $command->limit
        );

        $dataPoints = array_map(
            fn (array $point) => new ExerciseStatisticsDataPointDto(
                date: $point['date']->format('Y-m-d'),
                sessionId: $point['sessionId'],
                maxWeightKg: $point['maxWeightKg'],
            ),
            $rawDataPoints
        );

        $summary = null;
        if (count($rawDataPoints) > 0) {
            $dataPointsForCalculation = array_map(
                fn (array $point) => [
                    'date' => $point['date']->format('Y-m-d'),
                    'sessionId' => $point['sessionId'],
                    'maxWeightKg' => $point['maxWeightKg'],
                ],
                $rawDataPoints
            );

            $rawSummary = $this->statisticsCalculator->calculateSummary($dataPointsForCalculation);

            $summary = new ExerciseStatisticsSummaryDto(
                totalSessions: $rawSummary['totalSessions'],
                personalRecord: $rawSummary['personalRecord'],
                prDate: $rawSummary['prDate'],
                firstWeight: $rawSummary['firstWeight'],
                latestWeight: $rawSummary['latestWeight'],
                progressPercentage: $rawSummary['progressPercentage'],
            );
        }

        return new ExerciseStatisticsDto(
            exerciseId: $command->exerciseId,
            exercise: ExerciseSimpleDto::fromEntity($exercise),
            dataPoints: $dataPoints,
            summary: $summary,
        );
    }
}
