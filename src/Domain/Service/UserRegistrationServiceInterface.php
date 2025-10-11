<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Application\Command\Auth\RegisterUserCommand;

interface UserRegistrationServiceInterface
{
    public function register(RegisterUserCommand $command): void;
}

