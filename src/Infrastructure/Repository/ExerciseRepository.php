<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Exercise;
use App\Domain\Repository\ExerciseRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Exercise>
 */
final class ExerciseRepository extends ServiceEntityRepository implements ExerciseRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exercise::class);
    }

    public function findAll(): array
    {
        /* @var list<Exercise> */
        return $this->createQueryBuilder('e')
            ->leftJoin('e.muscleCategory', 'mc')
            ->addSelect('mc')
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findById(string $id): ?Exercise
    {
        /* @var Exercise|null */
        return $this->createQueryBuilder('e')
            ->leftJoin('e.muscleCategory', 'mc')
            ->addSelect('mc')
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByFilters(?string $muscleCategoryId, ?string $search): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.muscleCategory', 'mc')
            ->addSelect('mc');

        if (null !== $muscleCategoryId) {
            $qb->andWhere('mc.id = :muscleCategoryId')
               ->setParameter('muscleCategoryId', $muscleCategoryId);
        }

        if (null !== $search && '' !== trim($search)) {
            $searchTerm = '%'.strtolower(trim($search)).'%';
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(e.name)', ':search'),
                    $qb->expr()->like('LOWER(e.nameEn)', ':search')
                )
            )->setParameter('search', $searchTerm);
        }

        /* @var list<Exercise> */
        return $qb->orderBy('e.name', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    public function findDoneExercisesByUser(
        string $userId,
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null,
        string $sortBy = 'lastUsedDate',
        string $sortOrder = 'asc',
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->select('e', 'COUNT(DISTINCT ws.id) as workoutsCount')
            ->innerJoin('e.workoutExercises', 'we')
            ->innerJoin('we.workoutSession', 'ws')
            ->innerJoin('e.muscleCategory', 'mc')
            ->addSelect('mc')
            ->where('ws.user = :userId')
            ->andWhere('ws.deletedAt IS NULL')
            ->setParameter('userId', $userId)
            ->groupBy('e.id')
            ->addGroupBy('mc.id');

        if (null !== $dateFrom) {
            $qb->andWhere('ws.date >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if (null !== $dateTo) {
            $qb->andWhere('ws.date <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }
        // Apply sorting
        switch ($sortBy) {
            case 'name':
                $qb->orderBy('e.name', strtoupper($sortOrder));
                break;
            case 'createdAt':
                $qb->orderBy('e.createdAt', strtoupper($sortOrder));
                break;
            case 'workoutsCount':
                $qb->orderBy('workoutsCount', 'DESC');
                break;
            case 'lastUsed':
                $qb->addSelect('MAX(ws.date) as HIDDEN lastUsedDate')
                    ->orderBy('lastUsedDate', strtoupper($sortOrder));
                break;
        }

        $result = $qb->getQuery()->getResult();

        // Transform result to expected format: [['exercise' => Exercise, 'workoutsCount' => int], ...]
        return array_map(
            fn (array $row) => [
                'exercise' => $row[0],
                'workoutsCount' => (int) $row['workoutsCount'],
            ],
            $result
        );
    }
}
