<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

final readonly class ExerciseStatisticsSummaryDto
{
    public function __construct(
        public int $totalSessions,
        public float $maxWeightRecord,
        public string $maxWeightRecordDate,
        public float $maxVolumeRecord,
        public string $maxVolumeRecordDate,
        public float $firstWeight,
        public float $latestWeight,
        public float $progressPercentage,
    ) {
    }
}
