<?php

declare(strict_types=1);

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
