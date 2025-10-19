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
use App\Infrastructure\Api\Output\ExerciseDto;
use Doctrine\DBAL\Exception\ConnectionException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/v1/exercises/{id}', name: 'get_exercise_by_id', methods: ['GET'])]
final class GetExerciseByIdController extends AbstractController
{
    public function __construct(
        private readonly ExerciseRepositoryInterface $exerciseRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        try {
            // Validate UUID format
            if (!Uuid::isValid($id)) {
                return $this->json([
                    'error' => 'Invalid exercise ID format',
                    'code' => 400,
                ], Response::HTTP_BAD_REQUEST);
            }

            // Find exercise
            $exercise = $this->exerciseRepository->findById($id);

            if (null === $exercise) {
                $this->logger->info('Exercise not found', ['id' => $id]);

                return $this->json([
                    'error' => 'Exercise not found',
                    'code' => 404,
                ], Response::HTTP_NOT_FOUND);
            }

            // Transform to DTO (default language: pl)
            $exerciseDto = ExerciseDto::fromEntity($exercise, lang: 'pl');

            return $this->json($exerciseDto, Response::HTTP_OK);
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
                'endpoint' => 'GET /api/v1/exercises/{id}',
            ]);

            return $this->json([
                'error' => 'An unexpected error occurred',
                'code' => 500,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
