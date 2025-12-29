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

namespace App\Infrastructure\Api\Output;

use App\Domain\Entity\Exercise;

final readonly class ExerciseSimpleDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $nameEn,
    ) {
    }

    public static function fromEntity(Exercise $exercise): self
    {
        return new self(
            id: $exercise->getId(),
            name: $exercise->getName(),
            nameEn: $exercise->getNameEn(),
        );
    }
}
