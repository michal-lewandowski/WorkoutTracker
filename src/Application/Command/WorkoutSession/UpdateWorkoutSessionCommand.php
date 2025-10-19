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

namespace App\Application\Command\WorkoutSession;

use App\Domain\Entity\WorkoutSession;

final readonly class UpdateWorkoutSessionCommand
{
    public function __construct(
        public WorkoutSession $workoutSession,
        public \DateTimeImmutable $date,
        public ?string $name,
        public ?string $notes
    ) {
    }
}

