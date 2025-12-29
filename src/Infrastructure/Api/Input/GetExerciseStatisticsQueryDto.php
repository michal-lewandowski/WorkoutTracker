<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetExerciseStatisticsQueryDto
{
    public function __construct(
        #[Assert\Date(message: 'Date from must be a valid date')]
        public ?string $dateFrom = null,
        #[Assert\Date(message: 'Date to must be a valid date')]
        public ?string $dateTo = null,
        #[Assert\Range(
            min: 1,
            max: 1000,
            notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}'
        )]
        public ?int $limit = 100,
    ) {
    }
}
