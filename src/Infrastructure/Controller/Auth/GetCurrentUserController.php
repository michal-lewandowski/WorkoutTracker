<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Auth;

use App\Domain\Entity\User;
use App\Infrastructure\Api\Output\UserDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/auth/me', name: 'auth_me', methods: ['GET'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class GetCurrentUserController extends AbstractController
{
    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $userDto = UserDto::fromEntity($user);

        return $this->json($userDto, Response::HTTP_OK);
    }
}
