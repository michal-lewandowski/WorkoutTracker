<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Query\Statistics;

use App\Application\Query\Statistics\GetDoneExercisesQuery;
use PHPUnit\Framework\TestCase;

final class GetDoneExercisesQueryTest extends TestCase
{
    public function testValidQuery(): void
    {
        $query = new GetDoneExercisesQuery(
            userId: '123e4567-e89b-12d3-a456-426614174000',
            dateFrom: new \DateTimeImmutable('2024-01-01'),
            dateTo: new \DateTimeImmutable('2024-12-31'),
            sortBy: 'name',
            sortOrder: 'asc',
        );

        self::assertSame('123e4567-e89b-12d3-a456-426614174000', $query->userId);
        self::assertEquals(new \DateTimeImmutable('2024-01-01'), $query->dateFrom);
        self::assertEquals(new \DateTimeImmutable('2024-12-31'), $query->dateTo);
        self::assertSame('name', $query->sortBy);
        self::assertSame('asc', $query->sortOrder);
    }

    public function testInvalidSortByThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sortBy value "invalid"');

        new GetDoneExercisesQuery(
            userId: '123e4567-e89b-12d3-a456-426614174000',
            sortBy: 'invalid',
        );
    }

    public function testInvalidSortOrderThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sortOrder value "invalid"');

        new GetDoneExercisesQuery(
            userId: '123e4567-e89b-12d3-a456-426614174000',
            sortOrder: 'invalid',
        );
    }

    public function testDateFromAfterDateToThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('dateFrom must be before or equal to dateTo');

        new GetDoneExercisesQuery(
            userId: '123e4567-e89b-12d3-a456-426614174000',
            dateFrom: new \DateTimeImmutable('2024-12-31'),
            dateTo: new \DateTimeImmutable('2024-01-01'),
        );
    }

    public function testDefaultValues(): void
    {
        $query = new GetDoneExercisesQuery(
            userId: '123e4567-e89b-12d3-a456-426614174000',
        );

        self::assertNull($query->dateFrom);
        self::assertNull($query->dateTo);
        self::assertSame('name', $query->sortBy);
        self::assertSame('asc', $query->sortOrder);
    }

    public function testAllSortByOptionsAreValid(): void
    {
        $validSortByOptions = ['name', 'createdAt', 'lastUsed'];

        foreach ($validSortByOptions as $sortBy) {
            $query = new GetDoneExercisesQuery(
                userId: '123e4567-e89b-12d3-a456-426614174000',
                sortBy: $sortBy,
            );

            self::assertSame($sortBy, $query->sortBy);
        }
    }

    public function testAllSortOrderOptionsAreValid(): void
    {
        $validSortOrderOptions = ['asc', 'desc'];

        foreach ($validSortOrderOptions as $sortOrder) {
            $query = new GetDoneExercisesQuery(
                userId: '123e4567-e89b-12d3-a456-426614174000',
                sortOrder: $sortOrder,
            );

            self::assertSame($sortOrder, $query->sortOrder);
        }
    }

    public function testDateFromEqualToDateToIsValid(): void
    {
        $date = new \DateTimeImmutable('2024-06-15');
        $query = new GetDoneExercisesQuery(
            userId: '123e4567-e89b-12d3-a456-426614174000',
            dateFrom: $date,
            dateTo: $date,
        );

        self::assertEquals($date, $query->dateFrom);
        self::assertEquals($date, $query->dateTo);
    }
}

