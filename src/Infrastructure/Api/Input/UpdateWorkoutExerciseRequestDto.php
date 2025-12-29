<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateWorkoutExerciseRequestDto
{
    /**
     * @param array<ExerciseSetInputDto> $sets
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Valid]
        #[Assert\Count(min: 1, max: 20)]
        public array $sets,
    ) {
    }
}
