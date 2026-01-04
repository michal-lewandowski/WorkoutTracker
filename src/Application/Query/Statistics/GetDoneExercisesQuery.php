<?php

declare(strict_types=1);

namespace App\Application\Query\Statistics;

final readonly class GetDoneExercisesQuery
{
    private const ALLOWED_SORT_BY = ['name', 'createdAt', 'lastUsed', 'workoutsCount'];
    private const ALLOWED_SORT_ORDER = ['asc', 'desc'];

    public function __construct(
        public string $userId,
        public ?\DateTimeImmutable $dateFrom = null,
        public ?\DateTimeImmutable $dateTo = null,
        public string $sortBy = 'name',
        public string $sortOrder = 'asc',
    ) {
        if (!in_array($this->sortBy, self::ALLOWED_SORT_BY, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid sortBy value "%s". Allowed values: %s', $this->sortBy, implode(', ', self::ALLOWED_SORT_BY)));
        }

        if (!in_array($this->sortOrder, self::ALLOWED_SORT_ORDER, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid sortOrder value "%s". Allowed values: %s', $this->sortOrder, implode(', ', self::ALLOWED_SORT_ORDER)));
        }

        if (null !== $this->dateFrom && null !== $this->dateTo && $this->dateFrom > $this->dateTo) {
            throw new \InvalidArgumentException('dateFrom must be before or equal to dateTo');
        }
    }
}
