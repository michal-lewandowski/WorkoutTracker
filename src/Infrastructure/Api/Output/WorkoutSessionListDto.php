<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

final readonly class WorkoutSessionListDto
{
    /**
     * @param array<WorkoutSessionDto> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $limit,
        public int $offset,
    ) {
    }
}
