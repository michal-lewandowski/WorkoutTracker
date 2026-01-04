<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Api\Output;

use App\Domain\Entity\Exercise;
use App\Domain\Entity\MuscleCategory;
use App\Infrastructure\Api\Output\DoneExerciseDto;
use PHPUnit\Framework\TestCase;

final class DoneExerciseDtoTest extends TestCase
{
    public function testFromEntityWithCount(): void
    {
        $muscleCategory = MuscleCategory::create(
            namePl: 'Triceps',
            nameEn: 'Triceps',
        );

        $exercise = Exercise::create(
            name: 'Diamentowe pompki',
            muscleCategory: $muscleCategory,
        );

        $dto = DoneExerciseDto::fromEntityWithCount($exercise, 5);

        self::assertSame($exercise->getId(), $dto->id);
        self::assertSame('Diamentowe pompki', $dto->name);
        self::assertSame(5, $dto->workoutsCount);
        self::assertSame($muscleCategory->getId(), $dto->muscleCategory->id);
        self::assertSame('Triceps', $dto->muscleCategory->namePl);
        self::assertSame('Triceps', $dto->muscleCategory->nameEn);
        self::assertInstanceOf(\DateTimeImmutable::class, $dto->createdAt);
        self::assertInstanceOf(\DateTimeImmutable::class, $dto->updatedAt);
    }

    public function testFromEntityWithCountZeroWorkouts(): void
    {
        $muscleCategory = MuscleCategory::create(
            namePl: 'Klatka piersiowa',
            nameEn: 'Chest',
        );

        $exercise = Exercise::create(
            name: 'Wyciskanie sztangi',
            muscleCategory: $muscleCategory,
        );

        $dto = DoneExerciseDto::fromEntityWithCount($exercise, 0);

        self::assertSame(0, $dto->workoutsCount);
    }

    public function testFromEntityWithCountLargeNumber(): void
    {
        $muscleCategory = MuscleCategory::create(
            namePl: 'Nogi',
            nameEn: 'Legs',
        );

        $exercise = Exercise::create(
            name: 'Przysiady',
            muscleCategory: $muscleCategory,
        );

        $dto = DoneExerciseDto::fromEntityWithCount($exercise, 150);

        self::assertSame('Przysiady', $dto->name);
        self::assertSame(150, $dto->workoutsCount);
    }

    public function testMuscleCategoryIsIncluded(): void
    {
        $muscleCategory = MuscleCategory::create(
            namePl: 'Barki',
            nameEn: 'Shoulders',
        );

        $exercise = Exercise::create(
            name: 'Wyciskanie nad głowę',
            muscleCategory: $muscleCategory,
        );

        $dto = DoneExerciseDto::fromEntityWithCount($exercise, 10);

        self::assertSame('Barki', $dto->muscleCategory->namePl);
        self::assertSame('Shoulders', $dto->muscleCategory->nameEn);
        self::assertInstanceOf(\DateTimeImmutable::class, $dto->muscleCategory->createdAt);
    }

    public function testDtoIsReadonly(): void
    {
        $reflection = new \ReflectionClass(DoneExerciseDto::class);
        
        self::assertTrue(
            $reflection->isReadOnly(),
            'DoneExerciseDto should be a readonly class'
        );
    }

    public function testDtoHasAllRequiredProperties(): void
    {
        $reflection = new \ReflectionClass(DoneExerciseDto::class);
        
        $requiredProperties = ['id', 'name', 'workoutsCount', 'muscleCategory', 'createdAt', 'updatedAt'];
        
        foreach ($requiredProperties as $propertyName) {
            self::assertTrue(
                $reflection->hasProperty($propertyName),
                sprintf('DoneExerciseDto must have property "%s"', $propertyName)
            );
        }
    }
}

