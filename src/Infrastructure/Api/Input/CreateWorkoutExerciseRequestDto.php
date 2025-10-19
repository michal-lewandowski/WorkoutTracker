<?php

declare(strict_types=1);

/*
 * This file is part of the proprietary project.
 *
 * This file and its contents are confidential and protected by copyright law.
 * Unauthorized copying, distribution, or disclosure of this content
 * is strictly prohibited without prior written consent from the author or
 * copyright owner.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

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
        public ?array $sets = null
    ) {}
}

