<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ExerciseNotFoundException extends \RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Exercise with ID "%s" not found', $id));
    }
}
