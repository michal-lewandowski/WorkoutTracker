<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class WorkoutSessionAccessDeniedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Access denied to this workout session');
    }
}
