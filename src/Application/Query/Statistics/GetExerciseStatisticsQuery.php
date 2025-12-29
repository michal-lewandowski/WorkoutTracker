<?php

declare(strict_types=1);

namespace App\Application\Query\Statistics;

final readonly class GetExerciseStatisticsQuery
{
    public function __construct(
        public string $exerciseId,
        public string $userId,
        public ?\DateTimeImmutable $dateFrom = null,
        public ?\DateTimeImmutable $dateTo = null,
        public ?int $limit = 100,
    ) {
    }
}
