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

namespace App\Application\Command\Statistics;

use App\Application\Exception\ExerciseNotFoundException;
use App\Domain\Repository\ExerciseRepositoryInterface;
use App\Domain\Repository\WorkoutExerciseRepositoryInterface;
use App\Domain\Service\StatisticsCalculator;
use App\Infrastructure\Api\Output\ExerciseSimpleDto;
use App\Infrastructure\Api\Output\ExerciseStatisticsDataPointDto;
use App\Infrastructure\Api\Output\ExerciseStatisticsDto;
use App\Infrastructure\Api\Output\ExerciseStatisticsSummaryDto;

final readonly class GetExerciseStatisticsHandler
{
    public function __construct(
        private ExerciseRepositoryInterface $exerciseRepository,
        private WorkoutExerciseRepositoryInterface $workoutExerciseRepository,
        private StatisticsCalculator $statisticsCalculator,
    ) {
    }

    public function handle(GetExerciseStatisticsCommand $command): ExerciseStatisticsDto
    {
        // Sprawdź czy ćwiczenie istnieje
        $exercise = $this->exerciseRepository->findById($command->exerciseId);
        if (null === $exercise) {
            throw new ExerciseNotFoundException(sprintf('Exercise with ID "%s" not found', $command->exerciseId));
        }

        // Pobierz dane o maksymalnej wadze z każdej sesji dla danego ćwiczenia
        $rawDataPoints = $this->workoutExerciseRepository->findMaxWeightPerSessionByExerciseAndUser(
            exerciseId: $command->exerciseId,
            userId: $command->userId,
            dateFrom: $command->dateFrom,
            dateTo: $command->dateTo,
            limit: $command->limit
        );

        // Mapuj surowe dane na DTOs
        $dataPoints = array_map(
            fn (array $point) => new ExerciseStatisticsDataPointDto(
                date: $point['date']->format('Y-m-d'),
                sessionId: $point['sessionId'],
                maxWeightKg: $point['maxWeightKg'],
            ),
            $rawDataPoints
        );

        // Oblicz podsumowanie statystyk, jeśli są jakieś dane
        $summary = null;
        if (count($rawDataPoints) > 0) {
            // Przygotuj proste struktury danych dla kalkulatora domenowego
            $dataPointsForCalculation = array_map(
                fn (array $point) => [
                    'date' => $point['date']->format('Y-m-d'),
                    'sessionId' => $point['sessionId'],
                    'maxWeightKg' => $point['maxWeightKg'],
                ],
                $rawDataPoints
            );

            // Oblicz surowe podsumowanie
            $rawSummary = $this->statisticsCalculator->calculateSummary($dataPointsForCalculation);

            // Mapuj na DTO
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

