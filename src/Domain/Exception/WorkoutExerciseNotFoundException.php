<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class WorkoutExerciseNotFoundException extends \RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Workout exercise with ID "%s" not found', $id));
    }
}
