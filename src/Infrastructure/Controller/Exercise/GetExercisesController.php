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

namespace App\Infrastructure\Controller\Exercise;

use App\Domain\Repository\ExerciseRepositoryInterface;
use App\Domain\Repository\MuscleCategoryRepositoryInterface;
use App\Infrastructure\Api\Input\GetExercisesQueryDto;
use App\Infrastructure\Api\Output\ExerciseDto;
use Doctrine\DBAL\Exception\ConnectionException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1/exercises', name: 'get_exercises', methods: ['GET'])]
final class GetExercisesController extends AbstractController
{
    public function __construct(
        private readonly ExerciseRepositoryInterface $exerciseRepository,
        private readonly MuscleCategoryRepositoryInterface $muscleCategoryRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        #[MapQueryString] ?GetExercisesQueryDto $queryDto = null,
    ): JsonResponse {
        // Default values if no query params provided
        $queryDto ??= new GetExercisesQueryDto();

        try {
            // Validate muscle category exists if provided
            if (null !== $queryDto->muscleCategoryId) {
                if (!Uuid::isValid($queryDto->muscleCategoryId)) {
                    return $this->json([
                        'error' => 'Invalid muscle category ID format',
                        'code' => 400,
                    ], Response::HTTP_BAD_REQUEST);
                }

                $muscleCategory = $this->muscleCategoryRepository->findById(
                    $queryDto->muscleCategoryId
                );

                if (null === $muscleCategory) {
                    $this->logger->warning('Muscle category not found', [
                        'muscleCategoryId' => $queryDto->muscleCategoryId,
                    ]);

                    return $this->json([
                        'error' => 'Muscle category not found',
                        'code' => 404,
                    ], Response::HTTP_NOT_FOUND);
                }
            }

            // Fetch exercises with filters
            $exercises = $this->exerciseRepository->findByFilters(
                muscleCategoryId: $queryDto->muscleCategoryId,
                search: $queryDto->search
            );

            // Transform to DTOs with language preference
            $exerciseDtos = array_map(
                fn ($exercise) => ExerciseDto::fromEntity($exercise, $queryDto->lang),
                $exercises
            );

            return $this->json($exerciseDtos, Response::HTTP_OK);
        } catch (ConnectionException $e) {
            $this->logger->error('Database connection failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json([
                'error' => 'Service temporarily unavailable',
                'code' => 500,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $e) {
            $this->logger->critical('Unexpected error occurred', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'endpoint' => 'GET /api/v1/exercises',
            ]);

            return $this->json([
                'error' => 'An unexpected error occurred',
                'code' => 500,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
