<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class WorkoutSessionNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct(
            sprintf('Workout session with id "%s" not found', $id)
        );
    }

    public static function withId(string $id): self
    {
        return new self(sprintf('Exercise with ID "%s" not found', $id));
    }
}
