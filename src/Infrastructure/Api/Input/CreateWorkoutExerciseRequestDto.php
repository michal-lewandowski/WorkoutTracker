<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateWorkoutExerciseRequestDto
{
    /**
     * @param array<ExerciseSetInputDto>|null $sets
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $workoutSessionId,
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $exerciseId,
        #[Assert\Valid]
        #[Assert\Count(max: 20)]
        public ?array $sets = null,
    ) {
    }
}
