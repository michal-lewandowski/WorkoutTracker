<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

final readonly class ExerciseStatisticsSummaryDto
{
    public function __construct(
        public int $totalSessions,
        public float $personalRecord,
        public string $prDate,
        public float $firstWeight,
        public float $latestWeight,
        public float $progressPercentage,
    ) {
    }
}
