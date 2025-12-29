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

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'exercise_sets')]
#[ORM\Index(name: 'idx_exercise_sets_workout_exercise_id', columns: ['workout_exercise_id'])]
readonly class ExerciseSet
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: WorkoutExercise::class, inversedBy: 'exerciseSets')]
    #[ORM\JoinColumn(name: 'workout_exercise_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private WorkoutExercise $workoutExercise;

    #[ORM\Column(name: 'sets_count', type: 'integer', nullable: false)]
    private int $setsCount;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $reps;

    #[ORM\Column(name: 'weight_grams', type: 'integer', nullable: false)]
    private int $weightGrams;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $createdAt;

    private function __construct(
        WorkoutExercise $workoutExercise,
        int $setsCount,
        int $reps,
        int $weightGrams,
    ) {
        $this->id = (string) Uuid::v4();
        $this->workoutExercise = $workoutExercise;
        $this->setsCount = $setsCount;
        $this->reps = $reps;
        $this->weightGrams = $weightGrams;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(
        WorkoutExercise $workoutExercise,
        int $setsCount,
        int $reps,
        int $weightGrams,
    ): self {
        return new self($workoutExercise, $setsCount, $reps, $weightGrams);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkoutExercise(): WorkoutExercise
    {
        return $this->workoutExercise;
    }

    public function getSetsCount(): int
    {
        return $this->setsCount;
    }

    public function getReps(): int
    {
        return $this->reps;
    }

    public function getWeightGrams(): int
    {
        return $this->weightGrams;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getWeightKg(): float
    {
        return $this->weightGrams / 1000;
    }
}
