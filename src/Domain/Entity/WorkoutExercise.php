<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'workout_exercises')]
#[ORM\Index(name: 'idx_workout_exercises_workout_session_id', columns: ['workout_session_id'])]
#[ORM\Index(name: 'idx_workout_exercises_exercise_id', columns: ['exercise_id'])]
class WorkoutExercise
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: WorkoutSession::class, inversedBy: 'workoutExercises')]
    #[ORM\JoinColumn(name: 'workout_session_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private WorkoutSession $workoutSession;

    #[ORM\ManyToOne(targetEntity: Exercise::class, inversedBy: 'workoutExercises')]
    #[ORM\JoinColumn(name: 'exercise_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Exercise $exercise;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: ExerciseSet::class, mappedBy: 'workoutExercise', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $exerciseSets;

    #[ORM\Column(type: 'integer')]
    private int $orderInWorkout;

    private function __construct(
        Uuid $id,
        WorkoutSession $workoutSession,
        Exercise $exercise,
        int $orderInWorkout,
    ) {
        $this->id = (string) $id;
        $this->workoutSession = $workoutSession;
        $this->exercise = $exercise;
        $this->createdAt = new \DateTimeImmutable();
        $this->exerciseSets = new ArrayCollection();
        $this->orderInWorkout = $orderInWorkout;
    }

    public static function create(
        Uuid $id,
        WorkoutSession $workoutSession,
        Exercise $exercise,
    ): self {
        return new self($id, $workoutSession, $exercise, $workoutSession->getWorkoutExercises()->count() + 1);
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

    public function getOrderInWorkout(): int
    {
        return $this->orderInWorkout;
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
