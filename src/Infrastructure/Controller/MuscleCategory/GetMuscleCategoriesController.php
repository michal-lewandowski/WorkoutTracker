<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MuscleCategory;

use App\Domain\Repository\MuscleCategoryRepositoryInterface;
use App\Infrastructure\Api\Output\MuscleCategoryDto;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/muscle-categories', name: 'get_muscle_categories', methods: ['GET'])]
final class GetMuscleCategoriesController extends AbstractController
{
    public function __construct(
        private readonly MuscleCategoryRepositoryInterface $muscleCategoryRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        try {
            $muscleCategories = $this->muscleCategoryRepository->findAll();

            if (empty($muscleCategories)) {
                $this->logger->warning('No muscle categories found in database');
            }

            $muscleCategoryDtos = array_map(
                fn ($category) => MuscleCategoryDto::fromEntity($category),
                $muscleCategories
            );

            return $this->json($muscleCategoryDtos, Response::HTTP_OK);
        } catch (ConnectionException $e) {
            $this->logger->error('Database connection failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json([
                'error' => 'Service temporarily unavailable',
                'code' => 500,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (ORMException $e) {
            $this->logger->error('ORM error occurred', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json([
                'error' => 'An error occurred while processing your request',
                'code' => 500,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $e) {
            $this->logger->critical('Unexpected error occurred', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json([
                'error' => 'An unexpected error occurred',
                'code' => 500,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
