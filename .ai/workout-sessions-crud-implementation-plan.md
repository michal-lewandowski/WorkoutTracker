# API Endpoint Implementation Plan: WorkoutSessions CRUD (GET, PUT, DELETE)

## 1. Przegląd punktów końcowych

Implementacja trzech punktów końcowych REST API dla zarządzania pojedynczymi sesjami treningowymi:

1. `GET /workout-sessions/{id}` - pobieranie szczegółów sesji treningowej z wszystkimi ćwiczeniami i seriami
2. `PUT /workout-sessions/{id}` - aktualizacja metadanych sesji treningowej (data, nazwa, notatki)
3. `DELETE /workout-sessions/{id}` - soft delete sesji treningowej

Wszystkie punkty końcowe wymagają uwierzytelnienia (bearer token JWT) oraz weryfikacji, że użytkownik ma dostęp tylko do własnych sesji treningowych.

## 2. Szczegóły żądania

### GET /workout-sessions/{id}

- **Metoda HTTP**: GET
- **Struktura URL**: `/workout-sessions/{id}`
- **Uwierzytelnianie**: Bearer token JWT
- **Parametry**:
  - **Path**:
    - `id` (string, format: uuid4): ID sesji treningowej

### PUT /workout-sessions/{id}

- **Metoda HTTP**: PUT
- **Struktura URL**: `/workout-sessions/{id}`
- **Uwierzytelnianie**: Bearer token JWT
- **Parametry**:
  - **Path**:
    - `id` (string, format: uuid4): ID sesji treningowej
- **Request Body**:
  ```json
  {
    "date": "2025-10-12", // Wymagane, format: date
    "name": "Updated name", // Opcjonalne, max 255 znaków, nullable
    "notes": "Updated notes" // Opcjonalne, nullable
  }
  ```

### DELETE /workout-sessions/{id}

- **Metoda HTTP**: DELETE
- **Struktura URL**: `/workout-sessions/{id}`
- **Uwierzytelnianie**: Bearer token JWT
- **Parametry**:
  - **Path**:
    - `id` (string, format: uuid4): ID sesji treningowej

## 3. Wykorzystywane typy

### DTOs wejściowe

1. **UpdateWorkoutSessionRequestDto**
   ```php
   final readonly class UpdateWorkoutSessionRequestDto
   {
       public function __construct(
           #[Assert\NotBlank]
           #[Assert\Date]
           public string $date,
           
           #[Assert\Length(max: 255)]
           public ?string $name = null,
           
           public ?string $notes = null
       ) {}
   }
   ```

### DTOs wyjściowe

1. **WorkoutSessionDetailDto** (już istnieje, potencjalnie do rozszerzenia)
   ```php
   final readonly class WorkoutSessionDetailDto
   {
       /**
        * @param array<WorkoutExerciseDto> $workoutExercises
        */
       public function __construct(
           public string $id,
           public string $userId,
           public string $date,
           public ?string $name,
           public ?string $notes,
           public array $workoutExercises,
           public \DateTimeImmutable $createdAt,
           public \DateTimeImmutable $updatedAt
       ) {}
   }
   ```

2. **WorkoutExerciseDto**
   ```php
   final readonly class WorkoutExerciseDto
   {
       /**
        * @param array<ExerciseSetDto> $sets
        */
       public function __construct(
           public string $id,
           public string $workoutSessionId,
           public string $exerciseId,
           public ExerciseSummaryDto $exercise,
           public array $sets,
           public int $orderIndex,
           public \DateTimeImmutable $createdAt,
           public \DateTimeImmutable $updatedAt
       ) {}
   }
   ```

3. **ExerciseSummaryDto**
   ```php
   final readonly class ExerciseSummaryDto
   {
       public function __construct(
           public string $id,
           public string $name,
           public string $nameEn,
           public string $muscleCategoryId
       ) {}
   }
   ```

4. **ExerciseSetDto**
   ```php
   final readonly class ExerciseSetDto
   {
       public function __construct(
           public string $id,
           public string $workoutExerciseId,
           public int $setNumber,
           public int $reps,
           public float $weight,
           public ?string $notes,
           public \DateTimeImmutable $createdAt
       ) {}
   }
   ```

### Command Modele

1. **UpdateWorkoutSessionCommand**
   ```php
   final readonly class UpdateWorkoutSessionCommand
   {
       public function __construct(
           public WorkoutSession $workoutSession,
           public \DateTimeImmutable $date,
           public ?string $name,
           public ?string $notes
       ) {}
   }
   ```

2. **DeleteWorkoutSessionCommand**
   ```php
   final readonly class DeleteWorkoutSessionCommand
   {
       public function __construct(
           public WorkoutSession $workoutSession,
           public User $deletedBy
       ) {}
   }
   ```

### Repository Interface (rozszerzenie istniejącego)

Metody do dodania w **WorkoutSessionRepositoryInterface**:
```php
interface WorkoutSessionRepositoryInterface
{
    // Istniejące metody
    public function save(WorkoutSession $workoutSession): void;
    public function findById(string $id): ?WorkoutSession;
    public function findByUserIdPaginated(...): array;
    public function countByUserId(...): int;
    
    // Nowe metody
    public function findByIdWithExercises(string $id): ?WorkoutSession;
    public function delete(WorkoutSession $workoutSession, User $deletedBy): void;
}
```

## 4. Szczegóły odpowiedzi

### GET /workout-sessions/{id}

- **Sukces (200 OK)**:
  ```json
  {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "userId": "550e8400-e29b-41d4-a716-446655440001",
    "date": "2025-10-11",
    "name": "Trening A - FBW",
    "notes": "Świetny trening!",
    "workoutExercises": [
      {
        "id": "550e8400-e29b-41d4-a716-446655440002",
        "workoutSessionId": "550e8400-e29b-41d4-a716-446655440000",
        "exerciseId": "550e8400-e29b-41d4-a716-446655440003",
        "exercise": {
          "id": "550e8400-e29b-41d4-a716-446655440003",
          "name": "Wyciskanie sztangi leżąc",
          "nameEn": "Barbell Bench Press",
          "muscleCategoryId": "550e8400-e29b-41d4-a716-446655440004"
        },
        "sets": [
          {
            "id": "550e8400-e29b-41d4-a716-446655440005",
            "workoutExerciseId": "550e8400-e29b-41d4-a716-446655440002",
            "setNumber": 1,
            "reps": 10,
            "weight": 80.5,
            "notes": null,
            "createdAt": "2025-10-11T10:35:00+00:00"
          }
        ],
        "orderIndex": 1,
        "createdAt": "2025-10-11T10:30:00+00:00",
        "updatedAt": "2025-10-11T10:30:00+00:00"
      }
    ],
    "createdAt": "2025-10-11T10:30:00+00:00",
    "updatedAt": "2025-10-11T10:45:00+00:00"
  }
  ```

- **Nie znaleziono (404 Not Found)**:
  ```json
  {
    "message": "Workout session not found",
    "code": 404
  }
  ```

- **Brak dostępu (403 Forbidden)**:
  ```json
  {
    "message": "Access denied",
    "code": 403
  }
  ```

- **Błąd uwierzytelnienia (401 Unauthorized)**:
  ```json
  {
    "message": "JWT Token not found",
    "code": 401
  }
  ```

### PUT /workout-sessions/{id}

- **Sukces (200 OK)**:
  ```json
  {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "userId": "550e8400-e29b-41d4-a716-446655440001",
    "date": "2025-10-12",
    "name": "Updated name",
    "notes": "Updated notes",
    "workoutExercises": [...],
    "createdAt": "2025-10-11T10:30:00+00:00",
    "updatedAt": "2025-10-12T14:20:00+00:00"
  }
  ```

- **Błąd walidacji (400 Bad Request)**:
  ```json
  {
    "message": "Validation Failed",
    "errors": {
      "date": ["This value should not be blank."]
    }
  }
  ```

- **Nie znaleziono (404 Not Found)**: jak wyżej
- **Brak dostępu (403 Forbidden)**: jak wyżej
- **Błąd uwierzytelnienia (401 Unauthorized)**: jak wyżej

### DELETE /workout-sessions/{id}

- **Sukces (204 No Content)**: pusta odpowiedź

- **Nie znaleziono (404 Not Found)**: jak wyżej
- **Brak dostępu (403 Forbidden)**: jak wyżej
- **Błąd uwierzytelnienia (401 Unauthorized)**: jak wyżej

## 5. Przepływ danych

### GET /workout-sessions/{id}

1. Kontroler odbiera żądanie HTTP z parametrem {id} w URL
2. Parametr id jest walidowany jako UUID
3. Kontroler pobiera zalogowanego użytkownika z SecuritySystem
4. Kontroler wywołuje repository `findByIdWithExercises(id)`
5. Repository wykonuje zapytanie Doctrine z JOIN FETCH dla relacji (workoutExercises, exercise, sets)
6. Kontroler weryfikuje czy sesja istnieje (404 jeśli nie)
7. Kontroler weryfikuje czy sesja należy do użytkownika (403 jeśli nie)
8. Kontroler mapuje encje na DTOs (WorkoutSessionDetailDto z zagnieżdżonymi WorkoutExerciseDto i ExerciseSetDto)
9. Kontroler zwraca odpowiedź HTTP 200 z danymi

### PUT /workout-sessions/{id}

1. Kontroler odbiera żądanie HTTP z parametrem {id} i body
2. Body jest mapowane na UpdateWorkoutSessionRequestDto przy użyciu #[MapRequestPayload]
3. Kontroler pobiera zalogowanego użytkownika z SecuritySystem
4. Kontroler wywołuje repository `findByIdWithExercises(id)` (aby mieć pełne dane do odpowiedzi)
5. Kontroler weryfikuje czy sesja istnieje (404 jeśli nie)
6. Kontroler weryfikuje czy sesja należy do użytkownika (403 jeśli nie)
7. Kontroler tworzy UpdateWorkoutSessionCommand i przekazuje do UpdateWorkoutSessionHandler
8. Handler aktualizuje encję WorkoutSession (metoda update())
9. Handler zapisuje encję przez repository
10. Kontroler mapuje zaktualizowaną encję na WorkoutSessionDetailDto
11. Kontroler zwraca odpowiedź HTTP 200 z danymi

### DELETE /workout-sessions/{id}

1. Kontroler odbiera żądanie HTTP z parametrem {id}
2. Parametr id jest walidowany jako UUID
3. Kontroler pobiera zalogowanego użytkownika z SecuritySystem
4. Kontroler wywołuje repository `findById(id)`
5. Kontroler weryfikuje czy sesja istnieje (404 jeśli nie)
6. Kontroler weryfikuje czy sesja należy do użytkownika (403 jeśli nie)
7. Kontroler tworzy DeleteWorkoutSessionCommand i przekazuje do DeleteWorkoutSessionHandler
8. Handler wykonuje soft delete (ustawia deletedAt i deletedBy)
9. Handler zapisuje encję przez repository
10. Kontroler zwraca odpowiedź HTTP 204 (No Content)

## 6. Względy bezpieczeństwa

1. **Uwierzytelnianie**:
   - Wymaga poprawnego tokenu JWT
   - Wykorzystanie usługi LexikJWTAuthenticationBundle

2. **Autoryzacja**:
   - Zapewnienie, że użytkownicy mogą operować tylko na własnych sesjach treningowych
   - Kontrola dostępu w kontrolerze poprzez sprawdzanie userId sesji vs zalogowanego użytkownika
   - Zwracanie 403 Forbidden jeśli użytkownik próbuje uzyskać dostęp do cudzej sesji

3. **Walidacja danych**:
   - Walidacja UUID dla parametru {id}
   - Szczegółowa walidacja wszystkich parametrów wejściowych przy użyciu Symfony Validator
   - Obsługa błędów walidacji przez ValidationExceptionListener

4. **Zabezpieczenia bazy danych**:
   - Używanie UUID zamiast sekwencyjnych ID
   - Parameteryzowane zapytania przez Doctrine (ochrona przed SQL injection)
   - Soft delete zamiast fizycznego usuwania danych

5. **Zapobieganie atakom**:
   - Walidacja formatu UUID w parametrach URL
   - Możliwość dodania rate-limitingu dla endpointów
   - CORS configuration

## 7. Obsługa błędów

1. **Nieprawidłowe dane wejściowe**:
   - Status: 400 Bad Request
   - Szczegółowe komunikaty o błędach walidacji
   - Używanie ValidationExceptionListener dla spójnego formatowania błędów

2. **Nie znaleziono zasobu**:
   - Status: 404 Not Found
   - Komunikat: "Workout session not found"
   - Logika: sesja nie istnieje lub została usunięta (soft delete)

3. **Brak dostępu**:
   - Status: 403 Forbidden
   - Komunikat: "Access denied"
   - Logika: użytkownik próbuje uzyskać dostęp do cudzej sesji

4. **Błąd uwierzytelnienia**:
   - Status: 401 Unauthorized
   - Standardowy format błędu z LexikJWTAuthenticationBundle

5. **Błędy wewnętrzne**:
   - Status: 500 Internal Server Error
   - Ogólny komunikat błędu (bez ujawniania szczegółów implementacji)
   - Logowanie szczegółów błędu przez Monolog

## 8. Rozważania dotyczące wydajności

1. **Lazy loading vs Eager loading**:
   - GET endpoint: użycie JOIN FETCH dla relacji (workoutExercises, exercise, sets)
   - Unikanie N+1 query problem
   - Jeden query SQL dla pobrania pełnych danych

2. **Indeksy bazy danych**:
   - Index na id (primary key)
   - Index na user_id + deletedAt (dla sprawdzania właściciela)

3. **Caching**:
   - Możliwość dodania cache dla często pobieranych sesji (opcjonalnie)
   - Invalidacja cache po UPDATE i DELETE

4. **Optymalizacja UPDATE**:
   - Doctrine automatycznie wykrywa zmiany (Unit of Work)
   - Tylko zmienione pola są aktualizowane w bazie
   - Automatyczna aktualizacja updatedAt

5. **Soft Delete**:
   - Brak kaskadowego usuwania relacji
   - Szybka operacja (tylko UPDATE dwóch pól)
   - Możliwość przywrócenia danych

## 9. Logika biznesowa w encji WorkoutSession

Dodać metody w encji `WorkoutSession` dla operacji UPDATE i DELETE:

```php
final class WorkoutSession
{
    // Istniejące właściwości...
    
    /**
     * Aktualizacja metadanych sesji
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
     * Soft delete sesji
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
     * Sprawdzenie czy sesja należy do użytkownika
     */
    public function belongsToUser(User $user): bool
    {
        return $this->user->getId() === $user->getId();
    }
}
```

## 10. Exceptions

Utworzyć dedykowane wyjątki domenowe:

1. **WorkoutSessionNotFoundException**
   ```php
   final class WorkoutSessionNotFoundException extends \RuntimeException
   {
       public function __construct(string $id)
       {
           parent::__construct(
               sprintf('Workout session with id "%s" not found', $id)
           );
       }
   }
   ```

2. **WorkoutSessionAccessDeniedException**
   ```php
   final class WorkoutSessionAccessDeniedException extends \RuntimeException
   {
       public function __construct()
       {
           parent::__construct('Access denied to this workout session');
       }
   }
   ```

## 11. Etapy wdrożenia

### Krok 1: Rozszerzenie encji WorkoutSession

1. Dodać metody `update()`, `delete()`, `belongsToUser()` w encji WorkoutSession

### Krok 2: Rozszerzenie Repository Interface i Implementation

1. Dodać metodę `findByIdWithExercises()` w WorkoutSessionRepositoryInterface
2. Implementować metodę w WorkoutSessionRepository z JOIN FETCH

### Krok 3: Utworzenie DTOs wyjściowych

1. Stworzyć WorkoutExerciseDto w Infrastructure/Api/Output/
2. Stworzyć ExerciseSummaryDto w Infrastructure/Api/Output/
3. Stworzyć ExerciseSetDto w Infrastructure/Api/Output/
4. Rozszerzyć WorkoutSessionDetailDto (dodać userId, workoutExercises jako array)

### Krok 4: Utworzenie DTO wejściowego dla UPDATE

1. Stworzyć UpdateWorkoutSessionRequestDto w Infrastructure/Api/Input/

### Krok 5: Utworzenie Command Models

1. Stworzyć UpdateWorkoutSessionCommand w Application/Command/WorkoutSession/
2. Stworzyć DeleteWorkoutSessionCommand w Application/Command/WorkoutSession/

### Krok 6: Utworzenie Exceptions

1. Stworzyć WorkoutSessionNotFoundException w Application/Exception/
2. Stworzyć WorkoutSessionAccessDeniedException w Application/Exception/

### Krok 7: Implementacja Command Handlers

1. Stworzyć UpdateWorkoutSessionHandler w Application/Command/WorkoutSession/
2. Stworzyć DeleteWorkoutSessionHandler w Application/Command/WorkoutSession/

### Krok 8: Implementacja kontrolerów

1. Stworzyć GetWorkoutSessionController w Infrastructure/Controller/WorkoutSession/
2. Stworzyć UpdateWorkoutSessionController in Infrastructure/Controller/WorkoutSession/
3. Stworzyć DeleteWorkoutSessionController in Infrastructure/Controller/WorkoutSession/

### Krok 9: Exception Listener

1. Zaktualizować lub stworzyć WorkoutSessionExceptionListener dla obsługi WorkoutSessionNotFoundException (404) i WorkoutSessionAccessDeniedException (403)

## 10. Dodatkowe uwagi

### Mapowanie WorkoutExercises na DTOs

W kontrolerach GET i PUT trzeba będzie mapować kolekcję WorkoutExercise na array WorkoutExerciseDto. Przykładowa helper metoda:

```php
private function mapWorkoutExercisesToDtos(Collection $workoutExercises): array
{
    return $workoutExercises->map(function (WorkoutExercise $we) {
        return new WorkoutExerciseDto(
            id: $we->getId(),
            workoutSessionId: $we->getWorkoutSession()->getId(),
            exerciseId: $we->getExercise()->getId(),
            exercise: new ExerciseSummaryDto(
                id: $we->getExercise()->getId(),
                name: $we->getExercise()->getName(),
                nameEn: $we->getExercise()->getNameEn(),
                muscleCategoryId: $we->getExercise()->getMuscleCategory()->getId()
            ),
            sets: $we->getSets()->map(function (ExerciseSet $set) {
                return new ExerciseSetDto(
                    id: $set->getId(),
                    workoutExerciseId: $set->getWorkoutExercise()->getId(),
                    setNumber: $set->getSetNumber(),
                    reps: $set->getReps(),
                    weight: $set->getWeight(),
                    notes: $set->getNotes(),
                    createdAt: $set->getCreatedAt()
                );
            })->toArray(),
            orderIndex: $we->getOrderIndex(),
            createdAt: $we->getCreatedAt(),
            updatedAt: $we->getUpdatedAt()
        );
    })->toArray();
}
```

### Wydajność JOIN FETCH

Dla endpointu GET użyć Doctrine Query Builder z JOIN FETCH:

```php
public function findByIdWithExercises(string $id): ?WorkoutSession
{
    return $this->createQueryBuilder('ws')
        ->leftJoin('ws.workoutExercises', 'we')
        ->leftJoin('we.exercise', 'e')
        ->leftJoin('we.sets', 's')
        ->addSelect('we', 'e', 's')
        ->where('ws.id = :id')
        ->andWhere('ws.deletedAt IS NULL')
        ->setParameter('id', $id)
        ->getQuery()
        ->getOneOrNullResult();
}
```

To zapewni pobranie wszystkich powiązanych danych w jednym zapytaniu SQL.

