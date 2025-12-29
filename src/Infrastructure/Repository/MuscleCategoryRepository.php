<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\MuscleCategory;
use App\Domain\Repository\MuscleCategoryRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MuscleCategory>
 */
final class MuscleCategoryRepository extends ServiceEntityRepository implements MuscleCategoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MuscleCategory::class);
    }

    /**
     * @return array<int, MuscleCategory>
     */
    public function findAll(): array
    {
        /* @var array<int, MuscleCategory> */
        return $this->createQueryBuilder('mc')
            ->orderBy('mc.namePl', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findById(string $id): ?MuscleCategory
    {
        try {

            return $this->createQueryBuilder('mc')
                ->where('mc.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (\Throwable $e) {
            dd($e);
        }
        /* @var MuscleCategory|null */
    }
}
