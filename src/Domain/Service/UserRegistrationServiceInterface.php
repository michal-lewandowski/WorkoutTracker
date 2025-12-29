<?php

declare(strict_types=1);

namespace App\Domain\Service;

interface UserRegistrationServiceInterface
{
    public function register(string $email, string $plainPassword): void;
}
