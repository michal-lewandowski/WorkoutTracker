<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ExerciseSetInputDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public int $setsCount,
        #[Assert\NotBlank]
        #[Assert\Type('integer')]
        #[Assert\Range(min: 1, max: 100)]
        public int $reps,
        #[Assert\NotBlank]
        #[Assert\Type('float')]
        #[Assert\PositiveOrZero]
        #[Assert\LessThanOrEqual(1000)]
        public float $weightKg,
    ) {
    }
}
