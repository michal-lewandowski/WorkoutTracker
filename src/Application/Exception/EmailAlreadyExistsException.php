<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class EmailAlreadyExistsException extends \DomainException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('Email "%s" is already registered', $email));
    }
}

