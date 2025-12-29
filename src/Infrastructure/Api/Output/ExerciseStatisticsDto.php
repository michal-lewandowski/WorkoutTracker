<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

final readonly class ExerciseStatisticsDto
{
    /**
     * @param array<ExerciseStatisticsDataPointDto> $dataPoints
     */
    public function __construct(
        public string $exerciseId,
        public ExerciseSimpleDto $exercise,
        public array $dataPoints,
        public ?ExerciseStatisticsSummaryDto $summary,
    ) {
    }
}
