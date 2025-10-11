<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'Invalid email format')]
        #[Assert\Length(max: 255, maxMessage: 'Email cannot be longer than {{ limit }} characters')]
        public string $email,
        
        #[Assert\NotBlank(message: 'Password is required')]
        #[Assert\Length(
            min: 8,
            minMessage: 'Password must be at least {{ limit }} characters long'
        )]
        #[Assert\Regex(
            pattern: '/^(?=.*[A-Z])(?=.*\d).+$/',
            message: 'Password must contain at least 1 uppercase letter and 1 digit'
        )]
        public string $password,
        
        #[Assert\NotBlank(message: 'Password confirmation is required')]
        #[Assert\IdenticalTo(
            propertyPath: 'password',
            message: 'Password confirmation does not match password'
        )]
        public string $passwordConfirmation,
    ) {}
}

