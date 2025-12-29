<?php

declare(strict_types=1);

namespace App\Application\Command\Auth;

use App\Domain\Service\UserRegistrationServiceInterface;

final readonly class RegisterUserHandler
{
    public function __construct(private UserRegistrationServiceInterface $registrationService)
    {
    }

    public function handle(RegisterUserCommand $command): void
    {
        $this->registrationService->register($command->email, $command->plainPassword);
    }
}
