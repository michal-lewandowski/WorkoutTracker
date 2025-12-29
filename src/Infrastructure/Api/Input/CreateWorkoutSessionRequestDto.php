<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateWorkoutSessionRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date,
        #[Assert\Length(max: 255)]
        public ?string $name = null,
        public ?string $notes = null,
    ) {
    }
}
