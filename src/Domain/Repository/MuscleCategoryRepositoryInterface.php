<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\MuscleCategory;

interface MuscleCategoryRepositoryInterface
{
    /**
     * @return array<int, MuscleCategory>
     */
    public function findAll(): array;
}

