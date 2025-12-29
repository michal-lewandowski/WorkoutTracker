<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetWorkoutSessionsQueryDto
{
    public function __construct(
        #[Assert\Range(min: 1, max: 100)]
        public int $limit = 50,
        #[Assert\GreaterThanOrEqual(0)]
        public int $offset = 0,
        #[Assert\Date]
        public ?string $dateFrom = null,
        #[Assert\Date]
        public ?string $dateTo = null,
        #[Assert\Choice(choices: ['date', 'createdAt'])]
        public string $sortBy = 'date',
        #[Assert\Choice(choices: ['asc', 'desc'])]
        public string $sortOrder = 'desc',
    ) {
    }
}
