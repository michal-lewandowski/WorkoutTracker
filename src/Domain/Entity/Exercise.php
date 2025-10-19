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
#[ORM\Table(name: 'exercises')]
#[ORM\UniqueConstraint(name: 'idx_exercises_name', columns: ['name'])]
#[ORM\Index(name: 'idx_exercises_muscle_category_id', columns: ['muscle_category_id'])]
class Exercise
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(name: 'name_en', type: 'string', length: 255, nullable: true)]
    private ?string $nameEn;

    #[ORM\ManyToOne(targetEntity: MuscleCategory::class, inversedBy: 'exercises')]
    #[ORM\JoinColumn(name: 'muscle_category_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private MuscleCategory $muscleCategory;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(targetEntity: WorkoutExercise::class, mappedBy: 'exercise')]
    private Collection $workoutExercises;

    private function __construct(
        string $name,
        MuscleCategory $muscleCategory,
        ?string $nameEn = null,
    ) {
        $this->id = (string) Uuid::v4();
        $this->name = $name;
        $this->nameEn = $nameEn;
        $this->muscleCategory = $muscleCategory;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->workoutExercises = new ArrayCollection();
    }

    public static function create(
        string $name,
        MuscleCategory $muscleCategory,
        ?string $nameEn = null,
    ): self {
        return new self($name, $muscleCategory, $nameEn);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameEn(): ?string
    {
        return $this->nameEn;
    }

    public function getMuscleCategory(): MuscleCategory
    {
        return $this->muscleCategory;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, WorkoutExercise>
     */
    public function getWorkoutExercises(): Collection
    {
        return $this->workoutExercises;
    }
}
