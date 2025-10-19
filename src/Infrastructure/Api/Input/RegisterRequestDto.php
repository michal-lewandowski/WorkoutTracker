<?php

declare(strict_types=1);

/*
 * This file is part of the proprietary project.
 *
 * This file and its contents are confidential and protected by copyright law.
 * Unauthorized copying, distribution, or disclosure of this content
 * is strictly prohibited without prior written consent from the author or
 * copyright owner.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

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
    ) {
    }
}
