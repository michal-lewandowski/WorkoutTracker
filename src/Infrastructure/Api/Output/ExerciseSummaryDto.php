<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

final readonly class ExerciseSummaryDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $nameEn,
        public string $muscleCategoryId,
    ) {
    }
}
