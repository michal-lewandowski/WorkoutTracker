<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Input;

final readonly class GetDoneExercisesQueryDto
{
    public function __construct(
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $sortBy = 'workoutsCount', // 'name', 'createdAt', 'lastUsed', 'workoutsCount'
        public ?string $sortOrder = 'asc', // 'asc', 'desc'
    ) {
    }
}
