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
}
