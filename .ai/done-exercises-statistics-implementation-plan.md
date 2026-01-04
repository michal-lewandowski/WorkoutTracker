# Done Exercises Statistics Endpoint - Implementation Plan

## Overview
Implementation of `GET /api/v1/statistics/done-exercises` endpoint that returns a list of exercises that the authenticated user has performed at least once in any workout session.

### Key Features
- Returns exercises with muscle category information
- Includes `workoutsCount` - number of workout sessions where each exercise was performed
- Supports date range filtering (`dateFrom`, `dateTo`)
- Supports sorting by: `name`, `createdAt`, `lastUsed`
- JWT authentication required

### Use Cases
- **Exercise Selection**: When user starts new workout, show exercises they've done before with frequency data
- **Progress Tracking**: Display "Most trained exercises" based on workoutsCount
- **Exercise History**: Show user's exercise repertoire with engagement metrics
- **Workout Planning**: Help users discover which exercises they've been neglecting (low workoutsCount)

## Response Structure
```json
[
    {
        "id": "c4194d1d-3eaf-4931-981c-9d7a8c01774a",
        "name": "Diamentowe pompki",
        "workoutsCount": 5,
        "muscleCategory": {
            "id": "a1a1b5ec-a68f-456d-8212-57ad47adfc22",
            "namePl": "Triceps",
            "nameEn": "Triceps",
            "createdAt": "2026-01-03T20:15:41+00:00"
        },
        "createdAt": "2026-01-03T20:15:41+00:00",
        "updatedAt": "2026-01-03T20:15:41+00:00"
    },
    {
        "id": "0205b054-1533-4b62-8b13-7ae40ca6c558",
        "name": "Dipy na barki",
        "workoutsCount": 12,
        "muscleCategory": {
            "id": "850746a2-1be9-4a71-a711-c8e7b7ff1fcc",
            "namePl": "Klatka piersiowa",
            "nameEn": "Chest",
            "createdAt": "2026-01-03T20:15:41+00:00"
        },
        "createdAt": "2026-01-03T20:15:41+00:00",
        "updatedAt": "2026-01-03T20:15:41+00:00"
    }
]
```

## Architecture Layers

### 1. API Layer (Infrastructure)

#### 1.1. Input DTO
**File:** `src/Infrastructure/Api/Input/GetDoneExercisesQueryDto.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Input;

final readonly class GetDoneExercisesQueryDto
{
    public function __construct(
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $sortBy = 'name', // 'name', 'createdAt', 'lastUsed' (future: 'workoutsCount')
        public ?string $sortOrder = 'asc', // 'asc', 'desc'
    ) {
    }
}
```

**Validation:**
- `dateFrom`: Optional, valid ISO 8601 date format (YYYY-MM-DD)
- `dateTo`: Optional, valid ISO 8601 date format (YYYY-MM-DD)
- `sortBy`: Optional, one of: 'name', 'createdAt', 'lastUsed'
  - Note: 'workoutsCount' sorting can be added in future versions
- `sortOrder`: Optional, one of: 'asc', 'desc'

#### 1.2. Output DTOs

**File:** `src/Infrastructure/Api/Output/DoneExerciseDto.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

use App\Domain\Entity\Exercise;

final readonly class DoneExerciseDto implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public int $workoutsCount,
        public MuscleCategoryDto $muscleCategory,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntityWithCount(Exercise $exercise, int $workoutsCount): self
    {
        return new self(
            id: $exercise->getId(),
            name: $exercise->getName(),
            workoutsCount: $workoutsCount,
            muscleCategory: MuscleCategoryDto::fromEntity($exercise->getMuscleCategory()),
            createdAt: $exercise->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $exercise->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'workoutsCount' => $this->workoutsCount,
            'muscleCategory' => $this->muscleCategory,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
```

**Note:** The factory method is renamed to `fromEntityWithCount()` to explicitly indicate that workout count is required.

**Note:** Reuse existing `MuscleCategoryDto` from `src/Infrastructure/Api/Output/MuscleCategoryDto.php`

#### 1.3. Controller
**File:** `src/Infrastructure/Controller/Statistics/GetDoneExercisesController.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Statistics;

use App\Application\Query\Statistics\GetDoneExercisesQuery;
use App\Application\Query\Statistics\GetDoneExercisesQueryHandler;
use App\Domain\Entity\User;
use App\Infrastructure\Api\Input\GetDoneExercisesQueryDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/statistics/done-exercises', name: 'get_done_exercises', methods: ['GET'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class GetDoneExercisesController extends AbstractController
{
    public function __construct(
        private readonly GetDoneExercisesQueryHandler $handler,
    ) {
    }

    public function __invoke(
        #[MapQueryString] ?GetDoneExercisesQueryDto $queryDto = null,
    ): JsonResponse {
        $queryDto ??= new GetDoneExercisesQueryDto();

        /** @var User $user */
        $user = $this->getUser();

        $dateFrom = null !== $queryDto->dateFrom
            ? new \DateTimeImmutable($queryDto->dateFrom)
            : null;

        $dateTo = null !== $queryDto->dateTo
            ? new \DateTimeImmutable($queryDto->dateTo)
            : null;

        $query = new GetDoneExercisesQuery(
            userId: $user->getId(),
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            sortBy: $queryDto->sortBy ?? 'name',
            sortOrder: $queryDto->sortOrder ?? 'asc',
        );

        $exercises = $this->handler->handle($query);

        return $this->json($exercises, Response::HTTP_OK);
    }
}
```

**Error Handling:**
- Invalid date format: Return 400 Bad Request (handled by Symfony's MapQueryString)
- Invalid sortBy/sortOrder: Validated in Query object
- No authentication: 401 Unauthorized (handled by IsGranted attribute)

### 2. Application Layer

#### 2.1. Query Object
**File:** `src/Application/Query/Statistics/GetDoneExercisesQuery.php`

```php
<?php

declare(strict_types=1);

namespace App\Application\Query\Statistics;

final readonly class GetDoneExercisesQuery
{
    private const ALLOWED_SORT_BY = ['name', 'createdAt', 'lastUsed'];
    private const ALLOWED_SORT_ORDER = ['asc', 'desc'];

    public function __construct(
        public string $userId,
        public ?\DateTimeImmutable $dateFrom = null,
        public ?\DateTimeImmutable $dateTo = null,
        public string $sortBy = 'name',
        public string $sortOrder = 'asc',
    ) {
        if (!in_array($this->sortBy, self::ALLOWED_SORT_BY, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid sortBy value "%s". Allowed values: %s',
                    $this->sortBy,
                    implode(', ', self::ALLOWED_SORT_BY)
                )
            );
        }

        if (!in_array($this->sortOrder, self::ALLOWED_SORT_ORDER, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid sortOrder value "%s". Allowed values: %s',
                    $this->sortOrder,
                    implode(', ', self::ALLOWED_SORT_ORDER)
                )
            );
        }

        if (null !== $this->dateFrom && null !== $this->dateTo && $this->dateFrom > $this->dateTo) {
            throw new \InvalidArgumentException('dateFrom must be before or equal to dateTo');
        }
    }
}
```

#### 2.2. Query Handler
**File:** `src/Application/Query/Statistics/GetDoneExercisesQueryHandler.php`

```php
<?php

declare(strict_types=1);

namespace App\Application\Query\Statistics;

use App\Domain\Repository\ExerciseRepositoryInterface;
use App\Infrastructure\Api\Output\DoneExerciseDto;

final readonly class GetDoneExercisesQueryHandler
{
    public function __construct(
        private ExerciseRepositoryInterface $exerciseRepository,
    ) {
    }

    /**
     * @return DoneExerciseDto[]
     */
    public function handle(GetDoneExercisesQuery $query): array
    {
        $exercisesWithCount = $this->exerciseRepository->findDoneExercisesByUser(
            userId: $query->userId,
            dateFrom: $query->dateFrom,
            dateTo: $query->dateTo,
            sortBy: $query->sortBy,
            sortOrder: $query->sortOrder,
        );

        return array_map(
            fn (array $item) => DoneExerciseDto::fromEntityWithCount(
                exercise: $item['exercise'],
                workoutsCount: $item['workoutsCount']
            ),
            $exercisesWithCount
        );
    }
}
```

**Note:** The repository now returns an array of arrays with structure `['exercise' => Exercise, 'workoutsCount' => int]`.

### 3. Domain Layer

#### 3.1. Repository Interface Update
**File:** `src/Domain/Repository/ExerciseRepositoryInterface.php`

Add new method:

```php
/**
 * Find all exercises that user has performed at least once in workout sessions.
 * Returns array of arrays with structure: ['exercise' => Exercise, 'workoutsCount' => int]
 *
 * @return array<int, array{exercise: Exercise, workoutsCount: int}>
 */
public function findDoneExercisesByUser(
    string $userId,
    ?\DateTimeImmutable $dateFrom = null,
    ?\DateTimeImmutable $dateTo = null,
    string $sortBy = 'name',
    string $sortOrder = 'asc',
): array;
```

### 4. Infrastructure Layer

#### 4.1. Repository Implementation
**File:** `src/Infrastructure/Repository/DoctrineExerciseRepository.php`

Add implementation:

```php
public function findDoneExercisesByUser(
    string $userId,
    ?\DateTimeImmutable $dateFrom = null,
    ?\DateTimeImmutable $dateTo = null,
    string $sortBy = 'name',
    string $sortOrder = 'asc',
): array {
    $qb = $this->createQueryBuilder('e')
        ->select('e', 'COUNT(DISTINCT ws.id) as workoutsCount')
        ->innerJoin('e.workoutExercises', 'we')
        ->innerJoin('we.workoutSession', 'ws')
        ->innerJoin('e.muscleCategory', 'mc')
        ->where('ws.user = :userId')
        ->setParameter('userId', $userId)
        ->groupBy('e.id')
        ->addGroupBy('mc.id');

    if (null !== $dateFrom) {
        $qb->andWhere('ws.date >= :dateFrom')
            ->setParameter('dateFrom', $dateFrom);
    }

    if (null !== $dateTo) {
        $qb->andWhere('ws.date <= :dateTo')
            ->setParameter('dateTo', $dateTo);
    }

    // Apply sorting
    switch ($sortBy) {
        case 'name':
            $qb->orderBy('e.name', strtoupper($sortOrder));
            break;
        case 'createdAt':
            $qb->orderBy('e.createdAt', strtoupper($sortOrder));
            break;
        case 'lastUsed':
            $qb->addSelect('MAX(ws.date) as HIDDEN lastUsedDate')
                ->orderBy('lastUsedDate', strtoupper($sortOrder));
            break;
    }

    $result = $qb->getQuery()->getResult();

    // Transform result to expected format: [['exercise' => Exercise, 'workoutsCount' => int], ...]
    return array_map(
        fn (array $row) => [
            'exercise' => $row[0],
            'workoutsCount' => (int) $row['workoutsCount'],
        ],
        $result
    );
}
```

**Query Explanation:**
- SELECT with `COUNT(DISTINCT ws.id)` to count unique workout sessions per exercise
- JOIN with `workout_exercises` table to get exercises used in sessions
- JOIN with `workout_sessions` table to filter by user and date
- JOIN with `muscle_categories` to eager load category data (avoid N+1)
- GROUP BY to get unique exercises with their workout counts
- Support for date range filtering
- Support for multiple sorting options
- Transform Doctrine result format to structured array

**Performance Considerations:**
- Indexes needed on:
  - `workout_sessions.user_id`
  - `workout_sessions.date`
  - `workout_exercises.exercise_id`
  - `workout_exercises.workout_session_id`

### 5. OpenAPI/Swagger Documentation

**File:** `docs/swagger.json`

Add to `paths` section:

```json
"/api/v1/statistics/done-exercises": {
    "get": {
        "tags": ["Statistics"],
        "summary": "Get exercises performed by user",
        "description": "Returns a list of all exercises that the authenticated user has performed at least once in any workout session. Supports date range filtering and sorting options.",
        "operationId": "getDoneExercises",
        "parameters": [
            {
                "name": "dateFrom",
                "in": "query",
                "description": "Filter sessions from this date (inclusive, ISO 8601 format: YYYY-MM-DD)",
                "required": false,
                "schema": {
                    "type": "string",
                    "format": "date",
                    "example": "2024-01-01"
                }
            },
            {
                "name": "dateTo",
                "in": "query",
                "description": "Filter sessions until this date (inclusive, ISO 8601 format: YYYY-MM-DD)",
                "required": false,
                "schema": {
                    "type": "string",
                    "format": "date",
                    "example": "2024-12-31"
                }
            },
            {
                "name": "sortBy",
                "in": "query",
                "description": "Sort results by field",
                "required": false,
                "schema": {
                    "type": "string",
                    "enum": ["name", "createdAt", "lastUsed"],
                    "default": "name"
                }
            },
            {
                "name": "sortOrder",
                "in": "query",
                "description": "Sort order",
                "required": false,
                "schema": {
                    "type": "string",
                    "enum": ["asc", "desc"],
                    "default": "asc"
                }
            }
        ],
        "responses": {
            "200": {
                "description": "List of exercises performed by user",
                "content": {
                    "application/json": {
                        "schema": {
                            "type": "array",
                            "items": {
                                "$ref": "#/components/schemas/DoneExercise"
                            }
                        },
                        "example": [
                            {
                                "id": "c4194d1d-3eaf-4931-981c-9d7a8c01774a",
                                "name": "Diamentowe pompki",
                                "workoutsCount": 5,
                                "muscleCategory": {
                                    "id": "a1a1b5ec-a68f-456d-8212-57ad47adfc22",
                                    "namePl": "Triceps",
                                    "nameEn": "Triceps",
                                    "createdAt": "2026-01-03T20:15:41+00:00"
                                },
                                "createdAt": "2026-01-03T20:15:41+00:00",
                                "updatedAt": "2026-01-03T20:15:41+00:00"
                            },
                            {
                                "id": "0205b054-1533-4b62-8b13-7ae40ca6c558",
                                "name": "Dipy na barki",
                                "workoutsCount": 12,
                                "muscleCategory": {
                                    "id": "850746a2-1be9-4a71-a711-c8e7b7ff1fcc",
                                    "namePl": "Klatka piersiowa",
                                    "nameEn": "Chest",
                                    "createdAt": "2026-01-03T20:15:41+00:00"
                                },
                                "createdAt": "2026-01-03T20:15:41+00:00",
                                "updatedAt": "2026-01-03T20:15:41+00:00"
                            }
                        ]
                    }
                }
            },
            "400": {
                "description": "Bad Request - Invalid query parameters",
                "content": {
                    "application/json": {
                        "schema": {
                            "$ref": "#/components/schemas/Error"
                        },
                        "example": {
                            "error": "Invalid sortBy value. Allowed values: name, createdAt, lastUsed",
                            "code": 400
                        }
                    }
                }
            },
            "401": {
                "description": "Unauthorized - User not authenticated",
                "content": {
                    "application/json": {
                        "schema": {
                            "$ref": "#/components/schemas/Error"
                        },
                        "example": {
                            "error": "Authentication required",
                            "code": 401
                        }
                    }
                }
            }
        },
        "security": [
            {
                "bearerAuth": []
            }
        ]
    }
}
```

Add to `components/schemas` section:

```json
"DoneExercise": {
    "type": "object",
    "required": ["id", "name", "workoutsCount", "muscleCategory", "createdAt", "updatedAt"],
    "properties": {
        "id": {
            "type": "string",
            "format": "uuid",
            "description": "Exercise UUID",
            "example": "c4194d1d-3eaf-4931-981c-9d7a8c01774a"
        },
        "name": {
            "type": "string",
            "description": "Exercise name",
            "example": "Diamentowe pompki"
        },
        "workoutsCount": {
            "type": "integer",
            "description": "Number of workout sessions where this exercise was performed",
            "example": 5,
            "minimum": 1
        },
        "muscleCategory": {
            "$ref": "#/components/schemas/MuscleCategory"
        },
        "createdAt": {
            "type": "string",
            "format": "date-time",
            "description": "Exercise creation timestamp (ISO 8601)",
            "example": "2026-01-03T20:15:41+00:00"
        },
        "updatedAt": {
            "type": "string",
            "format": "date-time",
            "description": "Exercise last update timestamp (ISO 8601)",
            "example": "2026-01-03T20:15:41+00:00"
        }
    }
}
```

**Note:** Ensure `MuscleCategory` schema is already defined in swagger.json. If not, add it:

```json
"MuscleCategory": {
    "type": "object",
    "required": ["id", "namePl", "nameEn", "createdAt"],
    "properties": {
        "id": {
            "type": "string",
            "format": "uuid",
            "example": "a1a1b5ec-a68f-456d-8212-57ad47adfc22"
        },
        "namePl": {
            "type": "string",
            "example": "Triceps"
        },
        "nameEn": {
            "type": "string",
            "example": "Triceps"
        },
        "createdAt": {
            "type": "string",
            "format": "date-time",
            "example": "2026-01-03T20:15:41+00:00"
        }
    }
}
```

## Testing Strategy

### 6.1. Unit Tests

#### Test Query Object Validation
**File:** `tests/Unit/Application/Query/Statistics/GetDoneExercisesQueryTest.php`

```php
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
}
```

#### Test DTO Transformation
**File:** `tests/Unit/Infrastructure/Api/Output/DoneExerciseDtoTest.php`

```php
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
        $muscleCategory = new MuscleCategory(
            namePl: 'Triceps',
            nameEn: 'Triceps',
        );

        $exercise = new Exercise(
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
    }

    public function testJsonSerialize(): void
    {
        $muscleCategory = new MuscleCategory(
            namePl: 'Triceps',
            nameEn: 'Triceps',
        );

        $exercise = new Exercise(
            name: 'Diamentowe pompki',
            muscleCategory: $muscleCategory,
        );

        $dto = DoneExerciseDto::fromEntityWithCount($exercise, 12);
        $json = $dto->jsonSerialize();

        self::assertIsArray($json);
        self::assertArrayHasKey('id', $json);
        self::assertArrayHasKey('name', $json);
        self::assertArrayHasKey('workoutsCount', $json);
        self::assertArrayHasKey('muscleCategory', $json);
        self::assertArrayHasKey('createdAt', $json);
        self::assertArrayHasKey('updatedAt', $json);
        self::assertSame(12, $json['workoutsCount']);
    }
}
```

### 6.2. Functional Tests

#### Test Controller
**File:** `tests/Functional/Controller/Statistics/GetDoneExercisesControllerTest.php`

```php
<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Statistics;

use App\Domain\Entity\Exercise;
use App\Domain\Entity\MuscleCategory;
use App\Domain\Entity\User;
use App\Domain\Entity\WorkoutExercise;
use App\Domain\Entity\WorkoutSession;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetDoneExercisesControllerTest extends WebTestCase
{
    private const API_URL = '/api/v1/statistics/done-exercises';

    public function testGetDoneExercisesSuccess(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        // Create test data
        $user = new User('test@example.com', 'password123');
        $em->persist($user);

        $muscleCategory = new MuscleCategory('Triceps', 'Triceps');
        $em->persist($muscleCategory);

        $exercise1 = new Exercise('Diamentowe pompki', $muscleCategory);
        $exercise2 = new Exercise('Wyciskanie', $muscleCategory);
        $em->persist($exercise1);
        $em->persist($exercise2);

        $session = new WorkoutSession($user, new \DateTimeImmutable('2024-01-15'));
        $em->persist($session);

        $workoutExercise = new WorkoutExercise($session, $exercise1);
        $workoutExercise->addSet(1, 10, 20.0, 60, null);
        $em->persist($workoutExercise);

        $em->flush();

        // Get JWT token
        $token = $this->getAuthToken($client, 'test@example.com', 'password123');

        // Make request
        $client->request('GET', self::API_URL, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $response = $client->getResponse();
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($response->getContent(), true);
        self::assertIsArray($data);
        self::assertCount(1, $data); // Only exercise1 was performed

        $exerciseData = $data[0];
        self::assertArrayHasKey('id', $exerciseData);
        self::assertArrayHasKey('name', $exerciseData);
        self::assertArrayHasKey('workoutsCount', $exerciseData);
        self::assertArrayHasKey('muscleCategory', $exerciseData);
        self::assertArrayHasKey('createdAt', $exerciseData);
        self::assertArrayHasKey('updatedAt', $exerciseData);
        self::assertSame('Diamentowe pompki', $exerciseData['name']);
        self::assertSame(1, $exerciseData['workoutsCount']);
    }

    public function testGetDoneExercisesWithDateFilter(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        // Create test data with multiple sessions
        $user = new User('test2@example.com', 'password123');
        $em->persist($user);

        $muscleCategory = new MuscleCategory('Chest', 'Chest');
        $em->persist($muscleCategory);

        $exercise = new Exercise('Bench Press', $muscleCategory);
        $em->persist($exercise);

        // Session in 2024
        $session2024 = new WorkoutSession($user, new \DateTimeImmutable('2024-06-15'));
        $em->persist($session2024);
        $we2024 = new WorkoutExercise($session2024, $exercise);
        $we2024->addSet(1, 10, 60.0, 90, null);
        $em->persist($we2024);

        // Session in 2025
        $session2025 = new WorkoutSession($user, new \DateTimeImmutable('2025-01-15'));
        $em->persist($session2025);
        $we2025 = new WorkoutExercise($session2025, $exercise);
        $we2025->addSet(1, 10, 65.0, 90, null);
        $em->persist($we2025);

        $em->flush();

        $token = $this->getAuthToken($client, 'test2@example.com', 'password123');

        // Filter only 2024 sessions
        $client->request('GET', self::API_URL . '?dateFrom=2024-01-01&dateTo=2024-12-31', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertSame('Bench Press', $data[0]['name']);
        self::assertSame(1, $data[0]['workoutsCount']); // Only one session in 2024
    }

    public function testGetDoneExercisesSorting(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $user = new User('test3@example.com', 'password123');
        $em->persist($user);

        $muscleCategory = new MuscleCategory('Arms', 'Arms');
        $em->persist($muscleCategory);

        $exerciseA = new Exercise('AAA Exercise', $muscleCategory);
        $exerciseZ = new Exercise('ZZZ Exercise', $muscleCategory);
        $em->persist($exerciseA);
        $em->persist($exerciseZ);

        $session = new WorkoutSession($user, new \DateTimeImmutable('2024-01-15'));
        $em->persist($session);

        $weA = new WorkoutExercise($session, $exerciseA);
        $weA->addSet(1, 10, 20.0, 60, null);
        $em->persist($weA);

        $weZ = new WorkoutExercise($session, $exerciseZ);
        $weZ->addSet(1, 10, 20.0, 60, null);
        $em->persist($weZ);

        $em->flush();

        $token = $this->getAuthToken($client, 'test3@example.com', 'password123');

        // Test ascending order (default)
        $client->request('GET', self::API_URL . '?sortBy=name&sortOrder=asc', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(2, $data);
        self::assertSame('AAA Exercise', $data[0]['name']);
        self::assertSame(1, $data[0]['workoutsCount']);
        self::assertSame('ZZZ Exercise', $data[1]['name']);
        self::assertSame(1, $data[1]['workoutsCount']);

        // Test descending order
        $client->request('GET', self::API_URL . '?sortBy=name&sortOrder=desc', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(2, $data);
        self::assertSame('ZZZ Exercise', $data[0]['name']);
        self::assertSame(1, $data[0]['workoutsCount']);
        self::assertSame('AAA Exercise', $data[1]['name']);
        self::assertSame(1, $data[1]['workoutsCount']);
    }

    public function testUnauthorizedAccess(): void
    {
        $client = static::createClient();
        $client->request('GET', self::API_URL);

        self::assertResponseStatusCodeSame(401);
    }

    public function testInvalidSortByParameter(): void
    {
        $client = static::createClient();
        $user = new User('test4@example.com', 'password123');
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();

        $token = $this->getAuthToken($client, 'test4@example.com', 'password123');

        $client->request('GET', self::API_URL . '?sortBy=invalid', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        self::assertResponseStatusCodeSame(400);
    }

    public function testEmptyResultWhenNoExercisesPerformed(): void
    {
        $client = static::createClient();
        $user = new User('test5@example.com', 'password123');
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();

        $token = $this->getAuthToken($client, 'test5@example.com', 'password123');

        $client->request('GET', self::API_URL, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertCount(0, $data);
    }

    public function testWorkoutsCountWithMultipleSessions(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $user = new User('test6@example.com', 'password123');
        $em->persist($user);

        $muscleCategory = new MuscleCategory('Legs', 'Legs');
        $em->persist($muscleCategory);

        $exercise = new Exercise('Squats', $muscleCategory);
        $em->persist($exercise);

        // Create 5 different workout sessions with the same exercise
        for ($i = 1; $i <= 5; $i++) {
            $session = new WorkoutSession(
                $user, 
                new \DateTimeImmutable("2024-01-0{$i}")
            );
            $em->persist($session);

            $workoutExercise = new WorkoutExercise($session, $exercise);
            $workoutExercise->addSet(1, 10, 50.0 + $i, 90, null);
            $em->persist($workoutExercise);
        }

        $em->flush();

        $token = $this->getAuthToken($client, 'test6@example.com', 'password123');

        $client->request('GET', self::API_URL, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertSame('Squats', $data[0]['name']);
        self::assertSame(5, $data[0]['workoutsCount']);
    }

    private function getAuthToken($client, string $email, string $password): string
    {
        $client->request('POST', '/api/v1/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
        ]));

        $response = json_decode($client->getResponse()->getContent(), true);
        return $response['token'];
    }
}
```

### 6.3. Integration Tests

#### Test Repository Method
**File:** `tests/Integration/Repository/ExerciseRepositoryTest.php`

Add test method to existing test file:

```php
public function testFindDoneExercisesByUser(): void
{
    $em = $this->getEntityManager();
    $repository = $em->getRepository(Exercise::class);

    $user1 = new User('user1@example.com', 'password');
    $user2 = new User('user2@example.com', 'password');
    $em->persist($user1);
    $em->persist($user2);

    $muscleCategory = new MuscleCategory('Chest', 'Chest');
    $em->persist($muscleCategory);

    $exercise1 = new Exercise('Exercise 1', $muscleCategory);
    $exercise2 = new Exercise('Exercise 2', $muscleCategory);
    $exercise3 = new Exercise('Exercise 3', $muscleCategory);
    $em->persist($exercise1);
    $em->persist($exercise2);
    $em->persist($exercise3);

    // User1 performs exercise1 and exercise2
    $session1 = new WorkoutSession($user1, new \DateTimeImmutable('2024-01-15'));
    $em->persist($session1);
    $we1 = new WorkoutExercise($session1, $exercise1);
    $we1->addSet(1, 10, 20.0, 60, null);
    $em->persist($we1);

    $we2 = new WorkoutExercise($session1, $exercise2);
    $we2->addSet(1, 10, 30.0, 60, null);
    $em->persist($we2);

    // User2 performs exercise3
    $session2 = new WorkoutSession($user2, new \DateTimeImmutable('2024-01-15'));
    $em->persist($session2);
    $we3 = new WorkoutExercise($session2, $exercise3);
    $we3->addSet(1, 10, 40.0, 60, null);
    $em->persist($we3);

    $em->flush();

    // Test: User1 should see exercise1 and exercise2
    $doneExercises = $repository->findDoneExercisesByUser($user1->getId());
    self::assertCount(2, $doneExercises);
    
    $exerciseNames = array_map(fn($item) => $item['exercise']->getName(), $doneExercises);
    self::assertContains('Exercise 1', $exerciseNames);
    self::assertContains('Exercise 2', $exerciseNames);
    self::assertNotContains('Exercise 3', $exerciseNames);
    
    // Verify workoutsCount
    foreach ($doneExercises as $item) {
        self::assertSame(1, $item['workoutsCount']); // Each exercise in one session
    }

    // Test: User2 should see only exercise3
    $doneExercises = $repository->findDoneExercisesByUser($user2->getId());
    self::assertCount(1, $doneExercises);
    self::assertSame('Exercise 3', $doneExercises[0]['exercise']->getName());
    self::assertSame(1, $doneExercises[0]['workoutsCount']);
}

public function testFindDoneExercisesByUserWithDateFilter(): void
{
    $em = $this->getEntityManager();
    $repository = $em->getRepository(Exercise::class);

    $user = new User('user@example.com', 'password');
    $em->persist($user);

    $muscleCategory = new MuscleCategory('Legs', 'Legs');
    $em->persist($muscleCategory);

    $exercise = new Exercise('Squats', $muscleCategory);
    $em->persist($exercise);

    // Session in January 2024
    $session1 = new WorkoutSession($user, new \DateTimeImmutable('2024-01-15'));
    $em->persist($session1);
    $we1 = new WorkoutExercise($session1, $exercise);
    $we1->addSet(1, 10, 50.0, 90, null);
    $em->persist($we1);

    // Session in June 2024
    $session2 = new WorkoutSession($user, new \DateTimeImmutable('2024-06-15'));
    $em->persist($session2);
    $we2 = new WorkoutExercise($session2, $exercise);
    $we2->addSet(1, 10, 60.0, 90, null);
    $em->persist($we2);

    $em->flush();

    // Test: Filter only first half of 2024
    $doneExercises = $repository->findDoneExercisesByUser(
        userId: $user->getId(),
        dateFrom: new \DateTimeImmutable('2024-01-01'),
        dateTo: new \DateTimeImmutable('2024-03-31')
    );
    self::assertCount(1, $doneExercises);
    self::assertSame(1, $doneExercises[0]['workoutsCount']); // One session in date range

    // Test: No results for 2025
    $doneExercises = $repository->findDoneExercisesByUser(
        userId: $user->getId(),
        dateFrom: new \DateTimeImmutable('2025-01-01'),
        dateTo: new \DateTimeImmutable('2025-12-31')
    );
    self::assertCount(0, $doneExercises);
}

public function testFindDoneExercisesByUserWithMultipleSessions(): void
{
    $em = $this->getEntityManager();
    $repository = $em->getRepository(Exercise::class);

    $user = new User('user@example.com', 'password');
    $em->persist($user);

    $muscleCategory = new MuscleCategory('Arms', 'Arms');
    $em->persist($muscleCategory);

    $exercise = new Exercise('Bicep Curls', $muscleCategory);
    $em->persist($exercise);

    // Create 3 different sessions with the same exercise
    for ($i = 1; $i <= 3; $i++) {
        $session = new WorkoutSession(
            $user, 
            new \DateTimeImmutable("2024-01-{$i}5")
        );
        $em->persist($session);
        
        $we = new WorkoutExercise($session, $exercise);
        $we->addSet(1, 10, 20.0 + $i, 60, null);
        $em->persist($we);
    }

    $em->flush();

    // Test: Should return one exercise with workoutsCount = 3
    $doneExercises = $repository->findDoneExercisesByUser($user->getId());
    self::assertCount(1, $doneExercises);
    self::assertSame('Bicep Curls', $doneExercises[0]['exercise']->getName());
    self::assertSame(3, $doneExercises[0]['workoutsCount']);
}
```

## Implementation Checklist

- [ ] Create Input DTO: `GetDoneExercisesQueryDto.php`
- [ ] Create Output DTO: `DoneExerciseDto.php` (with `workoutsCount` field)
- [ ] Verify/reuse `MuscleCategoryDto.php` exists
- [ ] Create Query: `GetDoneExercisesQuery.php` (with validation)
- [ ] Create Query Handler: `GetDoneExercisesQueryHandler.php` (maps workoutsCount)
- [ ] Update Repository Interface: Add `findDoneExercisesByUser()` method (returns array with exercise + count)
- [ ] Implement Repository Method in `DoctrineExerciseRepository.php` (with COUNT(DISTINCT ws.id))
- [ ] Create Controller: `GetDoneExercisesController.php`
- [ ] Update Swagger documentation in `docs/swagger.json`:
  - [ ] Add `/api/v1/statistics/done-exercises` endpoint
  - [ ] Add `DoneExercise` schema with `workoutsCount` field
  - [ ] Verify `MuscleCategory` schema exists
- [ ] Write Unit Tests:
  - [ ] `GetDoneExercisesQueryTest.php` (validation tests)
  - [ ] `DoneExerciseDtoTest.php` (test workoutsCount field)
- [ ] Write Functional Tests: `GetDoneExercisesControllerTest.php`
  - [ ] Test basic functionality
  - [ ] Test date filtering
  - [ ] Test sorting
  - [ ] Test workoutsCount with multiple sessions
  - [ ] Test unauthorized access
  - [ ] Test empty results
- [ ] Write Integration Tests: Update `ExerciseRepositoryTest.php`
  - [ ] Test basic query
  - [ ] Test date filtering
  - [ ] Test workoutsCount calculation with multiple sessions
- [ ] Run all tests: `docker exec workouttracker-php-1 php bin/phpunit`
- [ ] Run PHPStan: `docker exec workouttracker-php-1 vendor/bin/phpstan analyse`
- [ ] Run PHP-CS-Fixer: `docker exec workouttracker-php-1 vendor/bin/php-cs-fixer fix`
- [ ] Manual testing via Swagger UI or Postman:
  - [ ] Verify workoutsCount is calculated correctly
  - [ ] Test with user who has same exercise in multiple sessions
- [ ] Update API documentation/README if needed

## Database Considerations

### Existing Indexes
Verify these indexes exist (should be from previous migrations):
- `workout_sessions.user_id`
- `workout_sessions.date`
- `workout_exercises.workout_session_id`
- `workout_exercises.exercise_id`

If missing, create migration:

```sql
CREATE INDEX idx_workout_sessions_user_id ON workout_sessions(user_id);
CREATE INDEX idx_workout_sessions_date ON workout_sessions(date);
CREATE INDEX idx_workout_exercises_session_id ON workout_exercises(workout_session_id);
CREATE INDEX idx_workout_exercises_exercise_id ON workout_exercises(exercise_id);
```

### Query Performance
Expected query execution (simplified):

```sql
SELECT 
    e.*,
    mc.*,
    COUNT(DISTINCT ws.id) as workoutsCount
FROM exercises e
INNER JOIN workout_exercises we ON we.exercise_id = e.id
INNER JOIN workout_sessions ws ON ws.id = we.workout_session_id
INNER JOIN muscle_categories mc ON mc.id = e.muscle_category_id
WHERE ws.user_id = :userId
  AND ws.date >= :dateFrom
  AND ws.date <= :dateTo
GROUP BY e.id, mc.id
ORDER BY e.name ASC;
```

**Example Result:**
```
| exercise_id | exercise_name      | workoutsCount |
|-------------|-------------------|---------------|
| uuid-1      | Diamentowe pompki | 5             |
| uuid-2      | Dipy na barki     | 12            |
```

## Edge Cases & Error Scenarios

1. **No exercises performed**: Return empty array `[]`
2. **Invalid date format**: Symfony MapQueryString returns 400 automatically
3. **Invalid sortBy/sortOrder**: Custom validation throws exception, return 400
4. **dateFrom > dateTo**: Validation in Query constructor, return 400
5. **Unauthorized access**: Security layer returns 401
6. **User has no sessions**: Return empty array `[]`
7. **Duplicate exercises in response**: Prevented by `GROUP BY` in query
8. **Missing muscleCategory**: Should not happen (FK constraint), but eager loading ensures data integrity
9. **workoutsCount calculation**: 
   - Uses `COUNT(DISTINCT ws.id)` to ensure each session is counted only once
   - If same exercise appears multiple times in one session (multiple sets), it counts as 1 workout
   - Minimum value is always 1 (exercise must be performed at least once to appear in results)
10. **Same exercise in multiple workout_exercises entries in one session**: Counted as 1 workout (DISTINCT prevents duplicates)

## Notes

- **Performance**: The query uses JOINs and GROUP BY which should be efficient with proper indexes
- **N+1 Problem Prevention**: JOIN with muscleCategory ensures all data is loaded in one query
- **Sorting by lastUsed**: Requires calculating MAX(ws.date) for each exercise
- **workoutsCount**: Uses `COUNT(DISTINCT ws.id)` to count unique workout sessions where the exercise appears
  - This provides users with engagement statistics for each exercise
  - Helps identify most frequently performed exercises
  - Useful for UI features like "Most trained exercises" or exercise popularity metrics
- **Future Enhancements**: 
  - Add pagination if exercise list grows large
  - Add `lastPerformed` date field to show when exercise was last used
  - Add filtering by muscle category
  - Add sorting by `workoutsCount` to show most/least performed exercises
  - Add `totalSets` or `totalVolume` metrics

## Dependencies

- Existing entities: `User`, `Exercise`, `MuscleCategory`, `WorkoutSession`, `WorkoutExercise`
- Existing repositories: `ExerciseRepositoryInterface`, `DoctrineExerciseRepository`
- JWT Authentication configured
- Security bundle configured

## Estimated Time

- Implementation: 2-3 hours
- Testing: 1-2 hours
- Documentation: 30 minutes
- **Total**: ~4-6 hours

