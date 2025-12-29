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

use App\Domain\Entity\WorkoutExercise;
use App\Domain\Repository\WorkoutExerciseRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutExercise>
 */
final class WorkoutExerciseRepository extends ServiceEntityRepository implements WorkoutExerciseRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutExercise::class);
    }

    public function save(WorkoutExercise $workoutExercise): void
    {
        $em = $this->getEntityManager();
        $em->persist($workoutExercise);
        $em->flush();
    }

    public function findById(string $id, ?string $userId = null): ?WorkoutExercise
    {
        $qb = $this->createQueryBuilder('we')
            ->select('we', 'e', 'es', 'ws', 'u', 'mc')
            ->leftJoin('we.exercise', 'e')
            ->leftJoin('e.muscleCategory', 'mc')
            ->leftJoin('we.exerciseSets', 'es')
            ->leftJoin('we.workoutSession', 'ws')
            ->leftJoin('ws.user', 'u')
            ->where('we.id = :id')
            ->setParameter('id', $id);

        if (null !== $userId) {
            $qb->andWhere('ws.user = :userId')
                ->setParameter('userId', $userId);
        }

        /** @var WorkoutExercise|null $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }

    public function delete(WorkoutExercise $workoutExercise): void
    {
        $this->getEntityManager()->remove($workoutExercise);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function findMaxWeightPerSessionByExerciseAndUser(
        string $exerciseId,
        string $userId,
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null,
        ?int $limit = 100,
    ): array {
        $qb = $this->createQueryBuilder('we')
            ->select(
                'ws.date as date',
                'ws.createdAt as createdAt',
                'ws.id as sessionId',
                'MAX(es.weightGrams) as maxWeightGrams'
            )
            ->join('we.workoutSession', 'ws')
            ->join('we.exerciseSets', 'es')
            ->join('ws.user', 'u')
            ->where('we.exercise = :exerciseId')
            ->andWhere('u.id = :userId')
            ->andWhere('ws.deletedAt IS NULL')
            ->setParameter('exerciseId', $exerciseId)
            ->setParameter('userId', $userId)
            ->groupBy('ws.id', 'ws.date')
            ->orderBy('ws.date', 'ASC')
            ->addOrderBy('ws.createdAt', 'ASC')
        ;

        if (null !== $dateFrom) {
            $qb->andWhere('ws.date >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if (null !== $dateTo) {
            $qb->andWhere('ws.date <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        $results = $qb->getQuery()->getResult();

        // Mapuj wyniki i konwertuj wagę z gramów na kg
        return array_map(
            fn (array $row) => [
                'date' => $row['date'],
                'sessionId' => (string) $row['sessionId'], // Konwertuj UUID na string
                'maxWeightKg' => (float) $row['maxWeightGrams'] / 1000,
            ],
            $results
        );
    }
}
