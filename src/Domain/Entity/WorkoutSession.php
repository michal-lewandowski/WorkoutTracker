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
#[ORM\Table(name: 'workout_sessions')]
#[ORM\Index(name: 'idx_workout_sessions_user_id_date', columns: ['user_id', 'date'])]
#[ORM\Index(name: 'idx_workout_sessions_deleted_by', columns: ['deleted_by'])]
#[ORM\Index(name: 'idx_workout_sessions_active', columns: ['user_id', 'date'])]
final class WorkoutSession
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'workoutSessions')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $date;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    #[ORM\Column(name: 'deleted_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'deleted_by', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $deletedBy;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(targetEntity: WorkoutExercise::class, mappedBy: 'workoutSession', cascade: ['persist', 'remove'])]
    private Collection $workoutExercises;

    private function __construct(
        User $user,
        \DateTimeImmutable $date,
        ?string $name = null,
        ?string $notes = null,
    ) {
        $this->id = (string) Uuid::v4();
        $this->user = $user;
        $this->date = $date;
        $this->name = $name;
        $this->notes = $notes;
        $this->deletedAt = null;
        $this->deletedBy = null;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->workoutExercises = new ArrayCollection();
    }

    public static function create(
        User $user,
        \DateTimeImmutable $date,
        ?string $name = null,
        ?string $notes = null,
    ): self {
        return new self($user, $date, $name, $notes);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function getDeletedBy(): ?User
    {
        return $this->deletedBy;
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

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    /**
     * Aktualizacja metadanych sesji treningowej.
     */
    public function update(
        \DateTimeImmutable $date,
        ?string $name = null,
        ?string $notes = null
    ): void {
        $this->date = $date;
        $this->name = $name;
        $this->notes = $notes;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Soft delete sesji treningowej.
     */
    public function delete(User $deletedBy): void
    {
        if ($this->isDeleted()) {
            throw new \LogicException('Workout session is already deleted');
        }

        $this->deletedAt = new \DateTimeImmutable();
        $this->deletedBy = $deletedBy;
    }

    /**
     * Sprawdzenie czy sesja należy do użytkownika.
     */
    public function belongsToUser(User $user): bool
    {
        return $this->user->getId() === $user->getId();
    }
}
