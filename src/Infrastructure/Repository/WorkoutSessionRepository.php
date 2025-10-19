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

namespace App\Infrastructure\Repository;

use App\Domain\Entity\WorkoutSession;
use App\Domain\Repository\WorkoutSessionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutSession>
 */
final class WorkoutSessionRepository extends ServiceEntityRepository implements WorkoutSessionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutSession::class);
    }

    public function save(WorkoutSession $workoutSession): void
    {
        $this->getEntityManager()->persist($workoutSession);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id, ?string $userId = null): ?WorkoutSession
    {
        $qb = $this->createQueryBuilder('ws')
            ->where('ws.id = :id')
            ->andWhere('ws.deletedAt IS NULL')
            ->setParameter('id', $id);

        if (null !== $userId) {
            $qb->andWhere('ws.user = :userId')
                ->setParameter('userId', $userId);
        }

        /** @var WorkoutSession|null $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }

    public function findByUserIdPaginated(
        string $userId,
        int $limit,
        int $offset,
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null,
        string $sortBy = 'date',
        string $sortOrder = 'desc'
    ): array {
        $qb = $this->createQueryBuilder('ws')
            ->where('ws.user = :userId')
            ->andWhere('ws.deletedAt IS NULL')
            ->setParameter('userId', $userId);

        if (null !== $dateFrom) {
            $qb->andWhere('ws.date >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if (null !== $dateTo) {
            $qb->andWhere('ws.date <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        // Mapowanie sortBy na rzeczywiste pola encji
        $sortField = match ($sortBy) {
            'date' => 'ws.date',
            'createdAt' => 'ws.createdAt',
            default => 'ws.date',
        };

        $qb->orderBy($sortField, strtoupper($sortOrder))
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        /** @var array<WorkoutSession> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    public function countByUserId(
        string $userId,
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null
    ): int {
        $qb = $this->createQueryBuilder('ws')
            ->select('COUNT(ws.id)')
            ->where('ws.user = :userId')
            ->andWhere('ws.deletedAt IS NULL')
            ->setParameter('userId', $userId);

        if (null !== $dateFrom) {
            $qb->andWhere('ws.date >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if (null !== $dateTo) {
            $qb->andWhere('ws.date <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findByIdWithExercises(string $id, string $userId): ?WorkoutSession
    {
        /** @var WorkoutSession|null $result */
        $result = $this->createQueryBuilder('ws')
            ->leftJoin('ws.workoutExercises', 'we')
            ->leftJoin('we.exercise', 'e')
            ->leftJoin('e.muscleCategory', 'mc')
            ->leftJoin('we.exerciseSets', 'es')
            ->addSelect('we', 'e', 'mc', 'es')
            ->where('ws.id = :id')
            ->andWhere('ws.user = :userId')
            ->andWhere('ws.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }
}

