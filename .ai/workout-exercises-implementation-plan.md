# API Endpoints Implementation Plan: Workout Exercises Management

## 1. Przegląd punktów końcowych

Implementacja trzech endpointów REST API do zarządzania ćwiczeniami w sesjach treningowych:

- **POST /api/v1/workout-exercises** - Dodaje nowe ćwiczenie do istniejącej sesji treningowej z opcjonalnymi setami
- **PUT /api/v1/workout-exercises/{id}** - Aktualizuje sety dla istniejącego ćwiczenia w sesji (zastępuje wszystkie istniejące sety)
- **DELETE /api/v1/workout-exercises/{id}** - Usuwa ćwiczenie z sesji treningowej (wraz z wszystkimi setami - cascade)

Wszystkie endpointy wymagają autentykacji JWT i zapewniają kontrolę dostępu na poziomie użytkownika. Implementacja następuje po wzorcu Hexagonal Architecture z użyciem Command Handlers.

---

## 2. Szczegóły żądań

### 2.1 POST /api/v1/workout-exercises

**Metoda HTTP:** POST

**Struktura URL:** `/api/v1/workout-exercises`

**Autentykacja:** Required (JWT Bearer token)

**Request Body (JSON):**
```json
{
  "workoutSessionId": "3a5b2509-e93b-4433-9520-dd51b1d6ef32",
  "exerciseId": "4b6c3610-f04c-5544-0631-ee62c2e7fg43",
  "sets": [
    {
      "setsCount": 3,
      "reps": 10,
      "weightKg": 70.5
    },
    {
      "setsCount": 2,
      "reps": 8,
      "weightKg": 75.0
    }
  ]
}
```

**Parametry:**
- **workoutSessionId** (string, UUID v4, WYMAGANE): ID sesji treningowej, do której dodajemy ćwiczenie
- **exerciseId** (string, UUID v4, WYMAGANE): ID ćwiczenia ze słownika exercises
- **sets** (array, OPCJONALNE): Tablica grup setów (max 20 elementów)
  - **setsCount** (integer, min: 1): Liczba setów w grupie (np. 3 w notacji 3x10@70kg)
  - **reps** (integer, min: 1, max: 100): Liczba powtórzeń w secie
  - **weightKg** (float, min: 0): Ciężar w kilogramach

**Uwagi:**
- Pole `sets` jest opcjonalne - użytkownik może dodać ćwiczenie bez setów i uzupełnić je później
- Jeśli `sets` jest podane, każdy element musi być prawidłowo zwalidowany

---

### 2.2 PUT /api/v1/workout-exercises/{id}

**Metoda HTTP:** PUT

**Struktura URL:** `/api/v1/workout-exercises/{id}`

**Autentykacja:** Required (JWT Bearer token)

**Path Parameters:**
- **id** (string, UUID v4, WYMAGANE): ID workout exercise do aktualizacji

**Request Body (JSON):**
```json
{
  "sets": [
    {
      "setsCount": 4,
      "reps": 12,
      "weightKg": 80.0
    }
  ]
}
```

**Parametry:**
- **sets** (array, WYMAGANE): Tablica grup setów (min: 1, max: 20 elementów)
  - **setsCount** (integer, min: 1): Liczba setów w grupie
  - **reps** (integer, min: 1, max: 100): Liczba powtórzeń w secie
  - **weightKg** (float, min: 0): Ciężar w kilogramach

**Uwagi:**
- Operacja **zastępuje wszystkie** istniejące sety nowymi
- Stare sety są usuwane (poprzez Doctrine cascade remove)
- Pole `sets` jest **wymagane** i nie może być puste

---

### 2.3 DELETE /api/v1/workout-exercises/{id}

**Metoda HTTP:** DELETE

**Struktura URL:** `/api/v1/workout-exercises/{id}`

**Autentykacja:** Required (JWT Bearer token)

**Path Parameters:**
- **id** (string, UUID v4, WYMAGANE): ID workout exercise do usunięcia

**Request Body:** Brak

**Uwagi:**
- Operacja usuwa workout exercise wraz z wszystkimi powiązanymi setami (Doctrine cascade)
- Nie usuwa samego Exercise ze słownika (tylko powiązanie z WorkoutSession)

---

## 3. Wykorzystywane typy

### 3.1 Input DTOs (do utworzenia)

#### ExerciseSetInputDto
```php
// src/Infrastructure/Api/Input/ExerciseSetInputDto.php
final readonly class ExerciseSetInputDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public int $setsCount,

        #[Assert\NotBlank]
        #[Assert\Type('integer')]
        #[Assert\Range(min: 1, max: 100)]
        public int $reps,

        #[Assert\NotBlank]
        #[Assert\Type('float')]
        #[Assert\PositiveOrZero]
        #[Assert\LessThanOrEqual(1000)]
        public float $weightKg
    ) {}
}
```

#### CreateWorkoutExerciseRequestDto
```php
// src/Infrastructure/Api/Input/CreateWorkoutExerciseRequestDto.php
final readonly class CreateWorkoutExerciseRequestDto
{
    /**
     * @param array<ExerciseSetInputDto>|null $sets
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $workoutSessionId,

        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $exerciseId,

        #[Assert\Valid]
        #[Assert\Count(max: 20)]
        public ?array $sets = null
    ) {}
}
```

#### UpdateWorkoutExerciseRequestDto
```php
// src/Infrastructure/Api/Input/UpdateWorkoutExerciseRequestDto.php
final readonly class UpdateWorkoutExerciseRequestDto
{
    /**
     * @param array<ExerciseSetInputDto> $sets
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Valid]
        #[Assert\Count(min: 1, max: 20)]
        public array $sets
    ) {}
}
```

### 3.2 Output DTOs (już istniejące)

- **WorkoutExerciseDto** - główny DTO zwracany przez POST i PUT
- **ExerciseSetDto** - reprezentacja pojedynczego setu w odpowiedzi
- **ExerciseSummaryDto** - podstawowe informacje o ćwiczeniu

### 3.3 Command Handlers (do utworzenia)

#### CreateWorkoutExerciseCommand
```php
// src/Application/Command/WorkoutExercise/CreateWorkoutExerciseCommand.php
final readonly class CreateWorkoutExerciseCommand
{
    /**
     * @param array<array{setsCount: int, reps: int, weightKg: float}>|null $sets
     */
    public function __construct(
        public string $userId,
        public string $workoutSessionId,
        public string $exerciseId,
        public ?array $sets = null
    ) {}
}
```

#### UpdateWorkoutExerciseCommand
```php
// src/Application/Command/WorkoutExercise/UpdateWorkoutExerciseCommand.php
final readonly class UpdateWorkoutExerciseCommand
{
    /**
     * @param array<array{setsCount: int, reps: int, weightKg: float}> $sets
     */
    public function __construct(
        public string $userId,
        public string $workoutExerciseId,
        public array $sets
    ) {}
}
```

#### DeleteWorkoutExerciseCommand
```php
// src/Application/Command/WorkoutExercise/DeleteWorkoutExerciseCommand.php
final readonly class DeleteWorkoutExerciseCommand
{
    public function __construct(
        public string $userId,
        public string $workoutExerciseId
    ) {}
}
```

### 3.4 Repository Interface (do utworzenia)

```php
// src/Domain/Repository/WorkoutExerciseRepositoryInterface.php
interface WorkoutExerciseRepositoryInterface
{
    public function save(WorkoutExercise $workoutExercise): void;
    public function findById(string $id): ?WorkoutExercise;
    public function delete(WorkoutExercise $workoutExercise): void;
    public function flush(): void;
}
```

### 3.5 Custom Exceptions (do utworzenia)

```php
// src/Application/Exception/WorkoutExerciseNotFoundException.php
final class WorkoutExerciseNotFoundException extends \RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Workout exercise with ID "%s" not found', $id));
    }
}

// src/Application/Exception/ExerciseNotFoundException.php
final class ExerciseNotFoundException extends \RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Exercise with ID "%s" not found', $id));
    }
}
```

---

## 4. Szczegóły odpowiedzi

### 4.1 POST /api/v1/workout-exercises

#### 201 Created - Sukces
```json
{
  "id": "5c7d4721-g15d-6655-1742-ff73d3f8gh54",
  "workoutSessionId": "3a5b2509-e93b-4433-9520-dd51b1d6ef32",
  "exerciseId": "4b6c3610-f04c-5544-0631-ee62c2e7fg43",
  "exercise": {
    "id": "4b6c3610-f04c-5544-0631-ee62c2e7fg43",
    "name": "Bench Press",
    "muscleCategoryId": "1a2b3c4d-5e6f-7g8h-9i0j-1k2l3m4n5o6p"
  },
  "exerciseSets": [
    {
      "id": "6d8e5832-h26e-7766-2853-gg84e4g9hi65",
      "workoutExerciseId": "5c7d4721-g15d-6655-1742-ff73d3f8gh54",
      "setsCount": 3,
      "reps": 10,
      "weightKg": 70.5,
      "createdAt": "2025-10-18T14:30:00Z"
    }
  ],
  "createdAt": "2025-10-18T14:30:00Z"
}
```

#### 400 Bad Request - Błędy walidacji
```json
{
  "errors": [
    {
      "field": "workoutSessionId",
      "message": "This value should be a valid UUID."
    },
    {
      "field": "sets[0].weightKg",
      "message": "This value should be positive or zero."
    }
  ]
}
```

#### 403 Forbidden - Brak dostępu
```json
{
  "message": "Access denied to this workout session",
  "code": 403
}
```

#### 404 Not Found - Zasób nie znaleziony
```json
{
  "message": "Workout session with ID \"3a5b2509-e93b-4433-9520-dd51b1d6ef32\" not found",
  "code": 404
}
```

### 4.2 PUT /api/v1/workout-exercises/{id}

#### 200 OK - Sukces
```json
{
  "id": "5c7d4721-g15d-6655-1742-ff73d3f8gh54",
  "workoutSessionId": "3a5b2509-e93b-4433-9520-dd51b1d6ef32",
  "exerciseId": "4b6c3610-f04c-5544-0631-ee62c2e7fg43",
  "exercise": {
    "id": "4b6c3610-f04c-5544-0631-ee62c2e7fg43",
    "name": "Bench Press",
    "muscleCategoryId": "1a2b3c4d-5e6f-7g8h-9i0j-1k2l3m4n5o6p"
  },
  "exerciseSets": [
    {
      "id": "7e9f6943-i37f-8877-3964-hh95f5h0ij76",
      "workoutExerciseId": "5c7d4721-g15d-6655-1742-ff73d3f8gh54",
      "setsCount": 4,
      "reps": 12,
      "weightKg": 80.0,
      "createdAt": "2025-10-18T15:00:00Z"
    }
  ],
  "createdAt": "2025-10-18T14:30:00Z"
}
```

#### 400/403/404 - Analogiczne jak w POST

### 4.3 DELETE /api/v1/workout-exercises/{id}

#### 204 No Content - Sukces
- Brak body w odpowiedzi
- Status code: 204

#### 403 Forbidden / 404 Not Found - Analogiczne jak wyżej

---

## 5. Przepływ danych

### 5.1 POST /api/v1/workout-exercises

```
1. Request → Controller
   ↓
2. Symfony MapRequestPayload deserializuje JSON → CreateWorkoutExerciseRequestDto
   ↓
3. Symfony Validator waliduje DTO (UUID, struktura sets)
   ↓
4. Controller tworzy CreateWorkoutExerciseCommand z DTO + User ID
   ↓
5. CreateWorkoutExerciseHandler.handle()
   ├─ WorkoutSessionRepository.findById()
   ├─ Sprawdzenie: WorkoutSession istnieje?
   │  └─ NIE → throw WorkoutSessionNotFoundException (404)
   ├─ Sprawdzenie: WorkoutSession.isDeleted()?
   │  └─ TAK → throw WorkoutSessionNotFoundException (404)
   ├─ Sprawdzenie: WorkoutSession.belongsToUser(user)?
   │  └─ NIE → throw WorkoutSessionAccessDeniedException (403)
   ├─ ExerciseRepository.findById()
   ├─ Sprawdzenie: Exercise istnieje?
   │  └─ NIE → throw ExerciseNotFoundException (404)
   ├─ WorkoutExercise::create(workoutSession, exercise)
   ├─ WorkoutExerciseRepository.save(workoutExercise)
   ├─ Dla każdego set w sets:
   │  ├─ Konwersja weightKg → weightGrams (weightKg * 1000)
   │  ├─ ExerciseSet::create(workoutExercise, setsCount, reps, weightGrams)
   │  └─ Dodanie do workoutExercise.exerciseSets (Doctrine cascade persist)
   └─ WorkoutExerciseRepository.flush()
   ↓
6. Handler zwraca WorkoutExercise entity
   ↓
7. Controller mapuje entity → WorkoutExerciseDto
   ↓
8. Response: JsonResponse(workoutExerciseDto, 201)
```

### 5.2 PUT /api/v1/workout-exercises/{id}

```
1. Request → Controller
   ↓
2. Symfony MapRequestPayload deserializuje JSON → UpdateWorkoutExerciseRequestDto
   ↓
3. Symfony Validator waliduje DTO
   ↓
4. Controller tworzy UpdateWorkoutExerciseCommand z DTO + User ID + Path param ID
   ↓
5. UpdateWorkoutExerciseHandler.handle()
   ├─ WorkoutExerciseRepository.findById(id)
   ├─ Sprawdzenie: WorkoutExercise istnieje?
   │  └─ NIE → throw WorkoutExerciseNotFoundException (404)
   ├─ Sprawdzenie: WorkoutExercise.workoutSession.belongsToUser(user)?
   │  └─ NIE → throw WorkoutSessionAccessDeniedException (403)
   ├─ Pobranie istniejących exerciseSets z workoutExercise
   ├─ Usunięcie wszystkich istniejących setów:
   │  └─ workoutExercise.exerciseSets.clear() (Doctrine cascade remove)
   ├─ Dla każdego set w command.sets:
   │  ├─ Konwersja weightKg → weightGrams
   │  ├─ ExerciseSet::create(workoutExercise, setsCount, reps, weightGrams)
   │  └─ workoutExercise.exerciseSets.add(exerciseSet)
   └─ WorkoutExerciseRepository.flush()
   ↓
6. Handler zwraca zaktualizowany WorkoutExercise entity
   ↓
7. Controller mapuje entity → WorkoutExerciseDto
   ↓
8. Response: JsonResponse(workoutExerciseDto, 200)
```

### 5.3 DELETE /api/v1/workout-exercises/{id}

```
1. Request → Controller
   ↓
2. Brak request body - tylko path parameter {id}
   ↓
3. Controller tworzy DeleteWorkoutExerciseCommand z User ID + Path param ID
   ↓
4. DeleteWorkoutExerciseHandler.handle()
   ├─ WorkoutExerciseRepository.findById(id)
   ├─ Sprawdzenie: WorkoutExercise istnieje?
   │  └─ NIE → throw WorkoutExerciseNotFoundException (404)
   ├─ Sprawdzenie: WorkoutExercise.workoutSession.belongsToUser(user)?
   │  └─ NIE → throw WorkoutSessionAccessDeniedException (403)
   ├─ WorkoutExerciseRepository.delete(workoutExercise)
   │  └─ Doctrine cascade remove usuwa również wszystkie ExerciseSets
   └─ WorkoutExerciseRepository.flush()
   ↓
5. Response: JsonResponse(null, 204)
```

---

## 6. Względy bezpieczeństwa

### 6.1 Uwierzytelnianie (Authentication)

- **JWT Bearer Token**: Wymagany w nagłówku `Authorization: Bearer <token>`
- **Symfony Security Guard**: Automatyczna walidacja tokenu przez LexikJWTAuthenticationBundle
- **Atrybut kontrolera**: `#[IsGranted('IS_AUTHENTICATED_FULLY')]`
- **Brak tokenu lub nieprawidłowy token** → 401 Unauthorized

### 6.2 Autoryzacja (Authorization)

#### Weryfikacja właściciela zasobu:
- **POST**: Sprawdzenie `WorkoutSession.belongsToUser(currentUser)`
- **PUT/DELETE**: Sprawdzenie `WorkoutExercise.workoutSession.belongsToUser(currentUser)`

#### Poziomy kontroli dostępu:
1. **Poziom sesji**: Użytkownik może dodawać ćwiczenia tylko do swoich sesji
2. **Poziom ćwiczenia**: Użytkownik może edytować/usuwać tylko ćwiczenia w swoich sesjach
3. **Poziom soft delete**: Nie można dodawać ćwicczeń do usuniętych sesji (`isDeleted()`)

#### Ochrona przed IDOR (Insecure Direct Object Reference):
- Każda operacja sprawdza właściciela przed wykonaniem
- UUID v4 utrudnia enumeration attack
- Brak dostępu → 403 Forbidden (nie 404, aby nie ujawniać istnienia zasobu)

### 6.3 Walidacja danych wejściowych

#### Walidacja na poziomie DTO (Symfony Validator):
- **UUID format**: `#[Assert\Uuid]` dla wszystkich ID
- **Typy danych**: `#[Assert\Type]` dla integer/float
- **Zakresy wartości**:
  - `setsCount`: min 1
  - `reps`: min 1, max 100
  - `weightKg`: min 0, max 1000 (zabezpieczenie przed błędami)
- **Rozmiar tablicy sets**: max 20 elementów

#### Walidacja na poziomie biznesowym (Command Handlers):
- Istnienie WorkoutSession
- Istnienie Exercise
- Sprawdzenie soft delete
- Sprawdzenie właściciela

### 6.4 SQL Injection Prevention

- **Doctrine ORM**: Automatyczne prepared statements
- **UUID walidacja**: Dodatkowa warstwa ochrony przed injection
- **Brak raw SQL queries**: Wszystkie zapytania przez Repository pattern

### 6.5 Rate Limiting (zalecane, opcjonalne)

- **Implementacja**: Symfony RateLimiter lub zewnętrzny service (Redis)
- **Limity**:
  - POST /workout-exercises: 60 żądań/minutę
  - PUT /workout-exercises/{id}: 120 żądań/minutę
  - DELETE: 60 żądań/minutę
- **Response**: 429 Too Many Requests

### 6.6 CORS (Cross-Origin Resource Sharing)

- Konfiguracja przez NelmioCorsBundle
- Dozwolone origins: frontend URL (np. http://localhost:3000)
- Dozwolone metody: GET, POST, PUT, DELETE, OPTIONS
- Dozwolone nagłówki: Authorization, Content-Type

---

## 7. Obsługa błędów

### 7.1 Kody statusu HTTP

| Kod | Nazwa | Przypadek użycia |
|-----|-------|------------------|
| 200 | OK | PUT - pomyślna aktualizacja |
| 201 | Created | POST - pomyślne utworzenie |
| 204 | No Content | DELETE - pomyślne usunięcie |
| 400 | Bad Request | Błędy walidacji (nieprawidłowe dane) |
| 401 | Unauthorized | Brak/nieprawidłowy JWT token |
| 403 | Forbidden | Brak dostępu do zasobu (nie właściciel) |
| 404 | Not Found | Zasób nie istnieje lub jest soft deleted |
| 500 | Internal Server Error | Błąd serwera/bazy danych |

### 7.2 Exception Handling Strategy

#### Custom Exception Listener
```php
// src/Infrastructure/EventListener/WorkoutExerciseExceptionListener.php
#[AsEventListener(event: ExceptionEvent::class)]
final class WorkoutExerciseExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        
        $response = match (true) {
            $exception instanceof WorkoutSessionNotFoundException,
            $exception instanceof WorkoutExerciseNotFoundException,
            $exception instanceof ExerciseNotFoundException
                => new JsonResponse(
                    ['message' => $exception->getMessage(), 'code' => 404],
                    Response::HTTP_NOT_FOUND
                ),
            
            $exception instanceof WorkoutSessionAccessDeniedException
                => new JsonResponse(
                    ['message' => $exception->getMessage(), 'code' => 403],
                    Response::HTTP_FORBIDDEN
                ),
            
            default => null
        };
        
        if (null !== $response) {
            $event->setResponse($response);
        }
    }
}
```

### 7.3 Scenariusze błędów dla POST

| Scenariusz | Exception | HTTP Status | Response |
|------------|-----------|-------------|----------|
| Nieprawidłowy UUID w workoutSessionId | ValidationException | 400 | ValidationError DTO |
| Nieprawidłowy UUID w exerciseId | ValidationException | 400 | ValidationError DTO |
| Nieprawidłowe wartości w sets | ValidationException | 400 | ValidationError DTO |
| WorkoutSession nie istnieje | WorkoutSessionNotFoundException | 404 | Error DTO |
| WorkoutSession jest usunięta | WorkoutSessionNotFoundException | 404 | Error DTO |
| Exercise nie istnieje | ExerciseNotFoundException | 404 | Error DTO |
| WorkoutSession nie należy do użytkownika | WorkoutSessionAccessDeniedException | 403 | Error DTO |
| Brak JWT token | AuthenticationException | 401 | Error DTO |
| Błąd bazy danych | \PDOException | 500 | Generic error |

### 7.4 Scenariusze błędów dla PUT

| Scenariusz | Exception | HTTP Status | Response |
|------------|-----------|-------------|----------|
| Nieprawidłowy UUID w path parameter | ValidationException | 400 | ValidationError DTO |
| Pusta tablica sets | ValidationException | 400 | ValidationError DTO |
| Nieprawidłowe wartości w sets | ValidationException | 400 | ValidationError DTO |
| WorkoutExercise nie istnieje | WorkoutExerciseNotFoundException | 404 | Error DTO |
| WorkoutExercise nie należy do użytkownika | WorkoutSessionAccessDeniedException | 403 | Error DTO |
| Brak JWT token | AuthenticationException | 401 | Error DTO |
| Błąd bazy danych | \PDOException | 500 | Generic error |

### 7.5 Scenariusze błędów dla DELETE

| Scenariusz | Exception | HTTP Status | Response |
|------------|-----------|-------------|----------|
| Nieprawidłowy UUID w path parameter | ValidationException | 400 | ValidationError DTO |
| WorkoutExercise nie istnieje | WorkoutExerciseNotFoundException | 404 | Error DTO |
| WorkoutExercise nie należy do użytkownika | WorkoutSessionAccessDeniedException | 403 | Error DTO |
| Brak JWT token | AuthenticationException | 401 | Error DTO |
| Błąd bazy danych | \PDOException | 500 | Generic error |

### 7.6 Logging

**Monolog Configuration:**
- **Level ERROR**: Błędy serwera (500), błędy bazy danych
- **Level WARNING**: Próby nieautoryzowanego dostępu (403)
- **Level INFO**: Pomyślne operacje (tylko w development)

**Nie logować:**
- JWT tokens
- Danych osobowych użytkowników
- Szczegółów ćwiczeń (mogą zawierać wrażliwe dane treningowe)

---

## 8. Rozważania dotyczące wydajności

### 8.1 Optymalizacja zapytań SQL

#### Problem N+1:
- **POST/PUT**: Eager loading dla `WorkoutExercise.exercise` relacji
- **GET w response**: Użycie JOIN FETCH w repository

```php
// WorkoutExerciseRepository
public function findById(string $id): ?WorkoutExercise
{
    return $this->createQueryBuilder('we')
        ->select('we', 'e', 'es', 'ws')
        ->leftJoin('we.exercise', 'e')
        ->leftJoin('we.exerciseSets', 'es')
        ->leftJoin('we.workoutSession', 'ws')
        ->where('we.id = :id')
        ->setParameter('id', $id)
        ->getQuery()
        ->getOneOrNullResult();
}
```

#### Indeksy bazodanowe (już istnieją):
- `idx_workout_exercises_workout_session_id` - dla JOIN z workout_sessions
- `idx_workout_exercises_exercise_id` - dla JOIN z exercises
- `idx_exercise_sets_workout_exercise_id` - dla JOIN z exercise_sets

### 8.2 Transakcje bazodanowe

- **POST z sets**: Jedna transakcja dla WorkoutExercise + wszystkie ExerciseSets
- **PUT**: Jedna transakcja dla usunięcia starych + dodania nowych setów
- **DELETE**: Jedna transakcja (cascade handled by Doctrine)

```php
// Doctrine flush() automatycznie wrapuje w transakcję
$this->workoutExerciseRepository->save($workoutExercise);
// ... dodawanie exerciseSets ...
$this->workoutExerciseRepository->flush(); // BEGIN + COMMIT
```

### 8.3 Walidacja wydajności

#### Request Body Size Limits:
- **Max sets**: 20 elementów (już walidowane)
- **Max request size**: 10KB (Symfony default, wystarczające)

#### Przykładowa kalkulacja:
```
20 sets × ~50 bytes/set = ~1KB dla sets
+ metadata (IDs, timestamps) = ~2KB total
```

### 8.4 Cache (opcjonalne, dla przyszłej optymalizacji)

#### Co można cache'ować:
- **Exercise entity**: Rzadko się zmienia, można cache'ować na 1h
- **MuscleCategory entity**: Prawie statyczne dane, cache 24h

#### Nie cache'ować:
- WorkoutExercise - dynamiczne dane użytkownika
- ExerciseSets - dane treningowe zmieniają się często

### 8.5 Connection Pooling

- **Doctrine DBAL**: Użycie connection pooling z PostgreSQL
- **Max connections**: 20-50 (zależnie od trafficu)
- **Timeout**: 30s

### 8.6 Monitoring

#### Metryki do śledzenia:
- Czas odpowiedzi endpointów (target: <200ms dla 95th percentile)
- Liczba zapytań SQL per request (target: max 5 queries)
- Database connection pool usage
- Rate limit hits

#### Tools:
- Symfony Profiler (development)
- Blackfire.io (profiling)
- New Relic / DataDog (production monitoring)

---

## 9. Etapy wdrożenia

### Faza 1: Przygotowanie infrastruktury

#### 1.1 Utworzenie Input DTOs
```bash
src/Infrastructure/Api/Input/ExerciseSetInputDto.php
src/Infrastructure/Api/Input/CreateWorkoutExerciseRequestDto.php
src/Infrastructure/Api/Input/UpdateWorkoutExerciseRequestDto.php
```
- Dodać property promotion
- Dodać Symfony Validator constraints
- Dodać strict types declaration

#### 1.2 Utworzenie Repository Interface i Implementation
```bash
src/Domain/Repository/WorkoutExerciseRepositoryInterface.php
src/Infrastructure/Repository/WorkoutExerciseRepository.php
```
- Interface z metodami: save, findById, delete, flush
- Implementation extends ServiceEntityRepository
- Dodać eager loading dla relacji (JOIN FETCH)

#### 1.3 Utworzenie Custom Exceptions
```bash
src/Application/Exception/WorkoutExerciseNotFoundException.php
src/Application/Exception/ExerciseNotFoundException.php
```
- Named constructors (withId)
- Rozszerzenie \RuntimeException

### Faza 2: Implementacja Command Layer (Application)

#### 2.1 CreateWorkoutExerciseCommand + Handler
```bash
src/Application/Command/WorkoutExercise/CreateWorkoutExerciseCommand.php
src/Application/Command/WorkoutExercise/CreateWorkoutExerciseHandler.php
```

**Handler logic:**
1. Pobranie WorkoutSession z repository
2. Walidacja: istnieje, nie jest usunięta, należy do użytkownika
3. Pobranie Exercise z repository
4. Walidacja: istnieje
5. Utworzenie WorkoutExercise entity
6. Iteracja przez sets (jeśli istnieją):
   - Konwersja weightKg → weightGrams
   - Utworzenie ExerciseSet entities
7. Save + flush

#### 2.2 UpdateWorkoutExerciseCommand + Handler
```bash
src/Application/Command/WorkoutExercise/UpdateWorkoutExerciseCommand.php
src/Application/Command/WorkoutExercise/UpdateWorkoutExerciseHandler.php
```

**Handler logic:**
1. Pobranie WorkoutExercise z repository (z eager loading)
2. Walidacja: istnieje, należy do użytkownika
3. Usunięcie istniejących exerciseSets (clear + flush)
4. Iteracja przez nowe sets:
   - Konwersja weightKg → weightGrams
   - Utworzenie nowych ExerciseSet entities
5. Flush

#### 2.3 DeleteWorkoutExerciseCommand + Handler
```bash
src/Application/Command/WorkoutExercise/DeleteWorkoutExerciseCommand.php
src/Application/Command/WorkoutExercise/DeleteWorkoutExerciseHandler.php
```

**Handler logic:**
1. Pobranie WorkoutExercise z repository
2. Walidacja: istnieje, należy do użytkownika
3. Delete + flush (cascade usunie ExerciseSets)

### Faza 3: Implementacja Controllers (Infrastructure)

#### 3.1 CreateWorkoutExerciseController
```bash
src/Infrastructure/Controller/WorkoutExercise/CreateWorkoutExerciseController.php
```

**Implementation checklist:**
- [ ] Route: POST /api/v1/workout-exercises
- [ ] IsGranted attribute
- [ ] MapRequestPayload dla CreateWorkoutExerciseRequestDto
- [ ] Pobranie zalogowanego User
- [ ] Wywołanie CreateWorkoutExerciseHandler
- [ ] Mapowanie WorkoutExercise → WorkoutExerciseDto
- [ ] Return JsonResponse z status 201

#### 3.2 UpdateWorkoutExerciseController
```bash
src/Infrastructure/Controller/WorkoutExercise/UpdateWorkoutExerciseController.php
```

**Implementation checklist:**
- [ ] Route: PUT /api/v1/workout-exercises/{id}
- [ ] IsGranted attribute
- [ ] Path parameter: string $id
- [ ] MapRequestPayload dla UpdateWorkoutExerciseRequestDto
- [ ] Pobranie zalogowanego User
- [ ] Wywołanie UpdateWorkoutExerciseHandler
- [ ] Mapowanie WorkoutExercise → WorkoutExerciseDto
- [ ] Return JsonResponse z status 200

#### 3.3 DeleteWorkoutExerciseController
```bash
src/Infrastructure/Controller/WorkoutExercise/DeleteWorkoutExerciseController.php
```

**Implementation checklist:**
- [ ] Route: DELETE /api/v1/workout-exercises/{id}
- [ ] IsGranted attribute
- [ ] Path parameter: string $id
- [ ] Pobranie zalogowanego User
- [ ] Wywołanie DeleteWorkoutExerciseHandler
- [ ] Return JsonResponse z status 204 i null body

### Faza 4: Exception Handling

#### 4.1 Exception Listener
```bash
src/Infrastructure/EventListener/WorkoutExerciseExceptionListener.php
```

**Implementation checklist:**
- [ ] Implement AsEventListener(ExceptionEvent::class)
- [ ] Handle WorkoutExerciseNotFoundException → 404
- [ ] Handle ExerciseNotFoundException → 404
- [ ] Handle WorkoutSessionAccessDeniedException → 403
- [ ] Use match expression dla czytelności

#### 4.2 Rozszerzenie istniejącego ValidationExceptionListener
- Upewnić się że obsługuje błędy walidacji dla nowych DTOs
- Testować format ValidationError response
