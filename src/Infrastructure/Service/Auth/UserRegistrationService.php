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

namespace App\Infrastructure\Service\Auth;

use App\Domain\Entity\User;
use App\Domain\Exception\EmailAlreadyExistsException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\UserRegistrationServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserRegistrationService implements UserRegistrationServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private LoggerInterface $logger,
    ) {
    }

    public function register(string $email, string $plainPassword): void
    {
        $this->logger->info('User registration attempt', [
            'email' => $email,
        ]);

        // Check if email already exists
        if ($this->userRepository->findByEmail($email)) {
            $this->logger->warning('Registration failed - email already exists', [
                'email' => $email,
            ]);
            throw new EmailAlreadyExistsException($email);
        }

        // Create user entity
        $user = User::create($email);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plainPassword
        );
        $user->setPasswordHash($hashedPassword);

        // Save to database
        try {
            $this->userRepository->save($user);
        } catch (\Exception $e) {
            $this->logger->error('Failed to save user to database', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to create user account');
        }

        $this->logger->info('User registered successfully', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
        ]);
    }
}
