<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'workout_exercises')]
#[ORM\Index(name: 'idx_workout_exercises_workout_session_id', columns: ['workout_session_id'])]
#[ORM\Index(name: 'idx_workout_exercises_exercise_id', columns: ['exercise_id'])]
final class WorkoutExercise
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: WorkoutSession::class, inversedBy: 'workoutExercises')]
    #[ORM\JoinColumn(name: 'workout_session_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private WorkoutSession $workoutSession;

    #[ORM\ManyToOne(targetEntity: Exercise::class, inversedBy: 'workoutExercises')]
    #[ORM\JoinColumn(name: 'exercise_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Exercise $exercise;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: ExerciseSet::class, mappedBy: 'workoutExercise', cascade: ['persist', 'remove'])]
    private Collection $exerciseSets;

    private function __construct(
        WorkoutSession $workoutSession,
        Exercise $exercise
    ) {
        $this->id = (string) new Ulid();
        $this->workoutSession = $workoutSession;
        $this->exercise = $exercise;
        $this->createdAt = new \DateTimeImmutable();
        $this->exerciseSets = new ArrayCollection();
    }

    public static function create(
        WorkoutSession $workoutSession,
        Exercise $exercise
    ): self {
        return new self($workoutSession, $exercise);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkoutSession(): WorkoutSession
    {
        return $this->workoutSession;
    }

    public function getExercise(): Exercise
    {
        return $this->exercise;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, ExerciseSet>
     */
    public function getExerciseSets(): Collection
    {
        return $this->exerciseSets;
    }
}

