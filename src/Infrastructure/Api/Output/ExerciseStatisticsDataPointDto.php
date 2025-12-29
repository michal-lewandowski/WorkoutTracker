<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

final readonly class ExerciseStatisticsDataPointDto
{
    public function __construct(
        public string $date,
        public string $sessionId,
        public float $maxWeightKg,
    ) {
    }
}
