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

namespace App\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'muscle_categories')]
#[ORM\UniqueConstraint(name: 'idx_muscle_categories_name_pl', columns: ['name_pl'])]
#[ORM\UniqueConstraint(name: 'idx_muscle_categories_name_en', columns: ['name_en'])]
class MuscleCategory
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'name_pl', type: 'string', length: 100, nullable: false)]
    private string $namePl;

    #[ORM\Column(name: 'name_en', type: 'string', length: 100, nullable: false)]
    private string $nameEn;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: Exercise::class, mappedBy: 'muscleCategory')]
    private Collection $exercises;

    private function __construct(
        string $namePl,
        string $nameEn,
    ) {
        $this->id = (string) Uuid::v4();
        $this->namePl = $namePl;
        $this->nameEn = $nameEn;
        $this->createdAt = new \DateTimeImmutable();
        $this->exercises = new ArrayCollection();
    }

    public static function create(string $namePl, string $nameEn): self
    {
        return new self($namePl, $nameEn);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNamePl(): string
    {
        return $this->namePl;
    }

    public function getNameEn(): string
    {
        return $this->nameEn;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, Exercise>
     */
    public function getExercises(): Collection
    {
        return $this->exercises;
    }
}
