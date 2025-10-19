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

final readonly class GetExercisesQueryDto
{
    public function __construct(
        #[Assert\Uuid(message: 'Invalid muscle category ID format')]
        public ?string $muscleCategoryId = null,
        #[Assert\Length(max: 255, maxMessage: 'Search parameter cannot exceed {{ limit }} characters')]
        public ?string $search = null,
        #[Assert\NotBlank]
        #[Assert\Choice(
            choices: ['pl', 'en'],
            message: 'Language must be either "pl" or "en"'
        )]
        public string $lang = 'pl',
    ) {
    }
}
