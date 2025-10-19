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

namespace App\Infrastructure\Controller\Auth;

use App\Application\Command\Auth\RegisterUserCommand;
use App\Application\Exception\EmailAlreadyExistsException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\UserRegistrationServiceInterface;
use App\Infrastructure\Api\Input\RegisterRequestDto;
use App\Infrastructure\Api\Output\AuthResponseDto;
use App\Infrastructure\Api\Output\UserDto;
use App\Infrastructure\Api\Output\ValidationErrorDto;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly UserRegistrationServiceInterface $registrationService,
        private JWTTokenManagerInterface $jwtManager,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    #[Route('/api/v1/auth/register', name: 'auth_register', methods: ['POST'])]
    public function register(
        #[MapRequestPayload] RegisterRequestDto $dto,
    ): JsonResponse {
        try {
            $command = RegisterUserCommand::fromDto($dto);
            $this->registrationService->register($command);

            $user = $this->userRepository->findByEmail($command->email);

            if (!$user) {
                throw new \RuntimeException('User not found after registration');
            }

            $authResponse = new AuthResponseDto(
                UserDto::fromEntity($user),
                $this->jwtManager->create($user)
            );

            return $this->json($authResponse, Response::HTTP_CREATED);

        } catch (EmailAlreadyExistsException $e) {
            $error = new ValidationErrorDto(
                message: 'Validation failed',
                errors: ['email' => ['Email is already registered']]
            );

            return $this->json($error, Response::HTTP_BAD_REQUEST);

        } catch (\RuntimeException $e) {
            return $this->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
