# API Endpoint Implementation Plan: Exercise Endpoints

## 1. Przegląd punktów końcowych

### GET /api/v1/exercises
Zwraca listę wszystkich ćwiczeń z opcjonalnym filtrowaniem według kategorii mięśniowej i wyszukiwaniem po nazwie. Obsługuje wielojęzyczność (polski/angielski).

### GET /api/v1/exercises/{id}
Zwraca szczegóły pojedynczego ćwiczenia na podstawie uuid4.

**Cel funkcjonalny:**
- Umożliwienie użytkownikom przeglądania dostępnych ćwiczeń
- Filtrowanie ćwiczeń według grup mięśniowych
- Wyszukiwanie ćwiczeń po nazwie
- Obsługa wielojęzyczności (PL/EN)
- Pobieranie szczegółów konkretnego ćwiczenia

## 2. Szczegóły żądania

### GET /api/v1/exercises

**Metoda HTTP:** GET

**Struktura URL:** `/api/v1/exercises?muscleCategoryId={uuid4}&search={term}&lang={pl|en}`

**Parametry:**
- **Opcjonalne:**
  - `muscleCategoryId` (string, format uuid4) - filtrowanie po kategorii mięśniowej
  - `search` (string) - wyszukiwanie częściowe po nazwie ćwiczenia (case-insensitive)
  - `lang` (string, enum: "pl" | "en", default: "pl") - język nazw ćwiczeń

**Nagłówki:**
- `Authorization: Bearer {JWT_token}` (wymagany)
- `Accept: application/json`

**Request Body:** Brak

### GET /api/v1/exercises/{id}

**Metoda HTTP:** GET

**Struktura URL:** `/api/v1/exercises/{id}` gdzie `{id}` to uuid4 ćwiczenia

**Parametry:**
- **Wymagane:**
  - `id` (path parameter, string, format uuid4) - identyfikator ćwiczenia

**Nagłówki:**
- `Authorization: Bearer {JWT_token}` (wymagany)
- `Accept: application/json`

**Request Body:** Brak

## 3. Wykorzystywane typy

### DTOs do stworzenia:

#### ExerciseDto (Output)
```php
final readonly class ExerciseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public MuscleCategoryDto $muscleCategory,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {}
    
    public static function fromEntity(Exercise $exercise, string $lang = 'pl'): self;
}
```

**Uwagi:**
- Pole `name` zawiera nazwę w wybranym języku (pl lub en)
- `muscleCategory` jest obiektem MuscleCategoryDto (już istnieje)
- Metoda `fromEntity` przyjmuje parametr `$lang` do wyboru języka

#### GetExercisesQueryDto (Input)
```php
final readonly class GetExercisesQueryDto
{
    public function __construct(
        #[Assert\uuid4(message: 'Invalid muscle category ID format')]
        public ?string $muscleCategoryId = null,
        
        #[Assert\Length(max: 255)]
        public ?string $search = null,
        
        #[Assert\Choice(choices: ['pl', 'en'], message: 'Language must be either "pl" or "en"')]
        public string $lang = 'pl',
    ) {}
}
```

**Uwagi:**
- Wszystkie parametry opcjonalne oprócz `lang` (ma wartość domyślną)
- Walidacja uuid4 dla `muscleCategoryId`
- Walidacja enum dla `lang`
- Limit długości dla `search`

### Repozytoria do stworzenia:

#### ExerciseRepositoryInterface (Domain/Repository)
```php
interface ExerciseRepositoryInterface
{
    /**
     * @return array<int, Exercise>
     */
    public function findAll(): array;
    
    public function findById(string $id): ?Exercise;
    
    /**
     * @return array<int, Exercise>
     */
    public function findByFilters(?string $muscleCategoryId, ?string $search): array;
}
```

#### ExerciseRepository (Infrastructure/Repository)
Implementacja interfejsu z logiką filtrowania przy użyciu Doctrine QueryBuilder.

## 4. Szczegóły odpowiedzi

### GET /api/v1/exercises

**Sukces (200 OK):**
```json
[
  {
    "id": "01HQXXXXXXXXXXXXXXXXXXXXXX",
    "name": "Wyciskanie sztangi na ławce płaskiej",
    "muscleCategory": {
      "id": "01HQYYYYYYYYYYYYYYYYYYYYYY",
      "namePl": "Klatka piersiowa",
      "nameEn": "Chest",
      "createdAt": "2024-01-15T10:30:00+00:00"
    },
    "createdAt": "2024-01-15T10:30:00+00:00",
    "updatedAt": "2024-01-15T10:30:00+00:00"
  }
]
```

**Uwagi:**
- Pole `name` zwraca `exercise.name` gdy `lang=pl` lub `exercise.nameEn` gdy `lang=en`
- Jeśli `nameEn` jest null a `lang=en`, fallback do `name` (polska nazwa)
- Pusta tablica `[]` gdy brak wyników
- Sortowanie alfabetyczne po nazwie

### GET /api/v1/exercises/{id}

**Sukces (200 OK):**
```json
{
  "id": "01HQXXXXXXXXXXXXXXXXXXXXXX",
  "name": "Wyciskanie sztangi na ławce płaskiej",
  "muscleCategory": {
    "id": "01HQYYYYYYYYYYYYYYYYYYYYYY",
    "namePl": "Klatka piersiowa",
    "nameEn": "Chest",
    "createdAt": "2024-01-15T10:30:00+00:00"
  },
  "createdAt": "2024-01-15T10:30:00+00:00",
  "updatedAt": "2024-01-15T10:30:00+00:00"
}
```

### Kody błędów

#### 400 Bad Request
```json
{
  "error": "Invalid uuid4 format",
  "code": 400
}
```
```json
{
  "error": "Invalid language parameter. Allowed values: pl, en",
  "code": 400
}
```

#### 401 Unauthorized
```json
{
  "message": "Invalid JWT Token"
}
```
**Uwaga:** Ten format pochodzi z LexikJWTAuthenticationBundle

#### 404 Not Found
```json
{
  "error": "Exercise not found",
  "code": 404
}
```
```json
{
  "error": "Muscle category not found",
  "code": 404
}
```

#### 500 Internal Server Error
```json
{
  "error": "Service temporarily unavailable",
  "code": 500
}
```

## 5. Przepływ danych

### GET /api/v1/exercises

1. **Request → Symfony Router** 
   - Route matching: `/api/v1/exercises`
   - Method: GET

2. **Security Layer (JWT Authentication)**
   - Weryfikacja tokena JWT w nagłówku Authorization
   - Jeśli brak/nieprawidłowy token → 401 Unauthorized
   - Jeśli OK → pobierz User z tokena

3. **Controller → MapQueryString**
   - Deserializacja query parameters do `GetExercisesQueryDto`
   - Symfony Validator automatycznie waliduje DTO
   - Jeśli walidacja fails → 422 Unprocessable Entity lub 400 Bad Request

4. **Controller → Repository**
   - Sprawdzenie czy `muscleCategoryId` istnieje (jeśli podane)
   - Jeśli nie istnieje → 404 Not Found
   - Wywołanie `ExerciseRepository::findByFilters($muscleCategoryId, $search)`

5. **Repository → Database (Doctrine ORM)**
   - Budowanie zapytania QueryBuilder:
     ```php
     $qb = $this->createQueryBuilder('e')
         ->leftJoin('e.muscleCategory', 'mc')
         ->addSelect('mc');
     
     if ($muscleCategoryId) {
         $qb->andWhere('mc.id = :muscleCategoryId')
            ->setParameter('muscleCategoryId', $muscleCategoryId);
     }
     
     if ($search) {
         $qb->andWhere(
             $qb->expr()->orX(
                 $qb->expr()->like('LOWER(e.name)', ':search'),
                 $qb->expr()->like('LOWER(e.nameEn)', ':search')
             )
         )->setParameter('search', '%' . strtolower($search) . '%');
     }
     
     $qb->orderBy('e.name', 'ASC');
     ```
   - Wykonanie zapytania SQL
   - Hydratacja wyników do obiektów Exercise

6. **Repository → Controller**
   - Zwrócenie tablicy obiektów `Exercise[]`

7. **Controller → Response**
   - Transformacja `Exercise[]` → `ExerciseDto[]` z uwzględnieniem parametru `lang`
   - Serializacja do JSON
   - Zwrócenie JsonResponse z kodem 200

8. **Error Handling na każdym etapie**
   - ConnectionException → 500 Service temporarily unavailable (log critical)
   - ORMException → 500 Processing error (log error)
   - Throwable → 500 Unexpected error (log critical)

### GET /api/v1/exercises/{id}

1. **Request → Symfony Router**
   - Route matching: `/api/v1/exercises/{id}`
   - Ekstrakcja parametru `id`

2. **Security Layer (JWT Authentication)**
   - Analogicznie jak w GET /exercises

3. **Controller → Validation**
   - Walidacja formatu uuid4 parametru `id`
   - Jeśli nieprawidłowy format → 400 Bad Request

4. **Controller → Repository**
   - Wywołanie `ExerciseRepository::findById($id)`

5. **Repository → Database**
   - Query: `SELECT e, mc FROM Exercise e LEFT JOIN e.muscleCategory mc WHERE e.id = :id`
   - Parametryzowane zapytanie (bezpieczeństwo)

6. **Repository → Controller**
   - Zwrócenie `?Exercise` (null jeśli nie znaleziono)

7. **Controller → Response**
   - Jeśli null → 404 Not Found
   - Jeśli Exercise → transformacja do ExerciseDto z lang='pl' (default)
   - Serializacja do JSON
   - Zwrócenie JsonResponse z kodem 200

## 6. Względy bezpieczeństwa

### Uwierzytelnianie (Authentication)
- **JWT Token wymagany** dla obu endpointów
- Konfiguracja w `config/packages/security.yaml`:
  ```yaml
  firewalls:
      main:
          stateless: true
          jwt: ~
  access_control:
      - { path: ^/api/v1/exercises, roles: IS_AUTHENTICATED_FULLY }
  ```
- Token weryfikowany przez LexikJWTAuthenticationBundle
- Automatyczne zwrócenie 401 dla brakującego/nieprawidłowego tokena

### Autoryzacja (Authorization)
- **Dostęp publiczny** dla zalogowanych użytkowników (role: ROLE_USER)
- Ćwiczenia są zasobem publicznym - każdy zalogowany użytkownik może je przeglądać
- Brak potrzeby Security Voter (nie ma resource ownership)

### Walidacja danych wejściowych
1. **uuid4 Format Validation**
   - Użycie `#[Assert\uuid4]` w DTO
   - Dodatkowa walidacja przez `uuid4::isValid($string)` w kontrolerze
   - Zapobiega przekazywaniu nieprawidłowych wartości do bazy

2. **Language Enum Validation**
   - `#[Assert\Choice(choices: ['pl', 'en'])]`
   - Zapobiega przekazywaniu nieoczekiwanych wartości

3. **Search String Sanitization**
   - Trim whitespace: `trim($search)`
   - Konwersja do lowercase w zapytaniu SQL
   - Doctrine automatycznie escapuje parametry (zapobiega SQL injection)

4. **Length Limits**
   - `#[Assert\Length(max: 255)]` na parametrze search
   - Zapobiega DoS przez bardzo długie stringi

### SQL Injection Prevention
- **Doctrine QueryBuilder z parametryzacją**:
  ```php
  ->andWhere('mc.id = :muscleCategoryId')
  ->setParameter('muscleCategoryId', $muscleCategoryId)
  ```
- Nigdy nie używamy konkatenacji stringów w zapytaniach

### Rate Limiting
- **Rekomendacja**: Implementacja rate limiting dla produkcji
- Sugerowane limity:
  - 100 requests/minute per user dla GET /exercises
  - 200 requests/minute per user dla GET /exercises/{id}
- Użycie Symfony Rate Limiter Component

### Logging bezpieczne
- **Nie logować**:
  - Tokenów JWT
  - Danych osobowych użytkowników
- **Logować**:
  - Błędy połączeń z bazą (error level)
  - Nieoczekiwane wyjątki (critical level)
  - Nieudane próby dostępu (warning level)

### CORS Headers
- Konfiguracja w `config/packages/nelmio_cors.yaml`
- Zezwolenie tylko dla frontend origin
- Allowed methods: GET, OPTIONS
- No credentials exposed in public endpoints

## 7. Obsługa błędów

### Hierarchia błędów (od najbardziej do najmniej szczegółowych)

#### 1. Błędy walidacji (400 Bad Request)

**Nieprawidłowy format uuid4:**
```php
use Symfony\Component\Uid\uuid4;

if (!uuid4::isValid($queryDto->muscleCategoryId)) {
    return $this->json([
        'error' => 'Invalid muscle category ID format',
        'code' => 400,
    ], Response::HTTP_BAD_REQUEST);
}
```

**Nieprawidłowy język:**
- Automatycznie obsługiwane przez Symfony Validator
- Zwraca 422 Unprocessable Entity z szczegółami walidacji

**Obsługa w kontrolerze:**
```php
try {
    // Symfony Validator może rzucić ValidationFailedException
} catch (ValidationFailedException $e) {
    return $this->json([
        'error' => 'Validation failed',
        'details' => $e->getViolations(),
        'code' => 400,
    ], Response::HTTP_BAD_REQUEST);
}
```

#### 2. Błędy autoryzacji (401 Unauthorized)

**Brak tokena JWT:**
- Obsługiwane automatycznie przez Symfony Security
- LexikJWTAuthenticationBundle zwraca:
```json
{
    "code": 401,
    "message": "JWT Token not found"
}
```

**Nieprawidłowy/wygasły token:**
```json
{
    "code": 401,
    "message": "Invalid JWT Token"
}
```

**Implementacja:**
- Brak dodatkowej obsługi w kontrolerze
- Security layer automatycznie blokuje nieautoryzowanych

#### 3. Błędy zasobów (404 Not Found)

**Ćwiczenie nie istnieje (GET /exercises/{id}):**
```php
$exercise = $this->exerciseRepository->findById($id);

if ($exercise === null) {
    $this->logger->info('Exercise not found', ['id' => $id]);
    
    return $this->json([
        'error' => 'Exercise not found',
        'code' => 404,
    ], Response::HTTP_NOT_FOUND);
}
```

**Kategoria mięśniowa nie istnieje (gdy filtrujemy):**
```php
if ($queryDto->muscleCategoryId !== null) {
    $muscleCategory = $this->muscleCategoryRepository->findById(
        $queryDto->muscleCategoryId
    );
    
    if ($muscleCategory === null) {
        $this->logger->warning('Muscle category not found', [
            'muscleCategoryId' => $queryDto->muscleCategoryId,
        ]);
        
        return $this->json([
            'error' => 'Muscle category not found',
            'code' => 404,
        ], Response::HTTP_NOT_FOUND);
    }
}
```

#### 4. Błędy bazy danych (500 Internal Server Error)

**Błąd połączenia z bazą:**
```php
use Doctrine\DBAL\Exception\ConnectionException;

try {
    $exercises = $this->exerciseRepository->findByFilters(
        $queryDto->muscleCategoryId,
        $queryDto->search
    );
} catch (ConnectionException $e) {
    $this->logger->error('Database connection failed', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    return $this->json([
        'error' => 'Service temporarily unavailable',
        'code' => 500,
    ], Response::HTTP_INTERNAL_SERVER_ERROR);
}
```

**Błędy ORM:**
```php
use Doctrine\ORM\ORMException;

catch (ORMException $e) {
    $this->logger->error('ORM error occurred', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    return $this->json([
        'error' => 'An error occurred while processing your request',
        'code' => 500,
    ], Response::HTTP_INTERNAL_SERVER_ERROR);
}
```

#### 5. Nieoczekiwane błędy (500 Internal Server Error)

```php
catch (\Throwable $e) {
    $this->logger->critical('Unexpected error occurred', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'endpoint' => 'GET /api/v1/exercises',
    ]);
    
    return $this->json([
        'error' => 'An unexpected error occurred',
        'code' => 500,
    ], Response::HTTP_INTERNAL_SERVER_ERROR);
}
```

### Mapa wszystkich możliwych błędów

| HTTP Status | Scenariusz | Komunikat | Log Level | Akcja |
|-------------|------------|-----------|-----------|-------|
| 400 | Nieprawidłowy format uuid4 | "Invalid muscle category ID format" | info | Walidacja na wejściu |
| 400 | Nieprawidłowy język | "Language must be either 'pl' or 'en'" | info | Symfony Validator |
| 400 | Search string za długi | "Search parameter too long" | info | Symfony Validator |
| 401 | Brak tokena JWT | "JWT Token not found" | warning | Security bundle |
| 401 | Nieprawidłowy token | "Invalid JWT Token" | warning | Security bundle |
| 404 | Ćwiczenie nie istnieje | "Exercise not found" | info | Sprawdzenie w repo |
| 404 | Kategoria nie istnieje | "Muscle category not found" | warning | Sprawdzenie w repo |
| 500 | Błąd połączenia DB | "Service temporarily unavailable" | error | Catch ConnectionException |
| 500 | Błąd ORM | "An error occurred while processing..." | error | Catch ORMException |
| 500 | Nieoczekiwany błąd | "An unexpected error occurred" | critical | Catch Throwable |

## 8. Rozważania dotyczące wydajności

### Optymalizacje zapytań bazodanowych

#### 1. Eager Loading relacji
```php
// Zamiast N+1 queries problem:
$qb = $this->createQueryBuilder('e')
    ->leftJoin('e.muscleCategory', 'mc')
    ->addSelect('mc')  // ← Eager load muscle category
    ->orderBy('e.name', 'ASC');
```
**Efekt**: 1 query zamiast N+1 (jeden dla ćwiczeń + jeden dla każdej kategorii)

#### 2. Indeksy bazodanowe (już istniejące)
- `idx_exercises_muscle_category_id` - dla filtrowania po kategorii
- `idx_exercises_name` (unique constraint) - dla wyszukiwania po nazwie

**Rekomendacja dodatkowych indeksów:**
```sql
-- Dla case-insensitive search (PostgreSQL)
CREATE INDEX idx_exercises_name_lower ON exercises (LOWER(name));
CREATE INDEX idx_exercises_name_en_lower ON exercises (LOWER(name_en));
```

#### 3. Limit i pagination (future enhancement)
Dla dużej liczby ćwiczeń (>100), rozważyć dodanie paginacji:
```php
#[Assert\Positive]
#[Assert\LessThanOrEqual(100)]
public int $limit = 50;

#[Assert\PositiveOrZero]
public int $offset = 0;
```

### Caching strategies

#### 1. HTTP Cache Headers (rekomendowane)
```php
$response = $this->json($exerciseDtos, Response::HTTP_OK);
$response->setPublic();
$response->setMaxAge(3600); // 1 hour
$response->headers->set('Vary', 'Authorization, Accept-Language');
return $response;
```

**Uwagi:**
- Ćwiczenia rzadko się zmieniają - idealne do cache
- Cache invalidation gdy admin dodaje/edytuje ćwiczenie
- `Vary: Authorization` - różne cache per user (JWT)

#### 2. Doctrine Query Result Cache (opcjonalnie)
```php
$qb->getQuery()
   ->setResultCacheLifetime(3600)
   ->setResultCacheId('exercises_all_pl')
   ->getResult();
```

**Uwaga:** Wymaga skonfigurowania cache pool (Redis/Memcached)

#### 3. Application-level cache (dla produkcji)
```php
use Symfony\Contracts\Cache\CacheInterface;

$exercises = $cache->get('exercises_filtered_' . md5(serialize($filters)), function() {
    return $this->exerciseRepository->findByFilters(...);
});
```

### Potencjalne wąskie gardła

#### 1. Full-text search bez indeksów
**Problem**: LIKE '%term%' jest wolny dla dużych tabel
**Rozwiązanie**:
- PostgreSQL Full-Text Search:
  ```sql
  ALTER TABLE exercises ADD COLUMN search_vector tsvector;
  CREATE INDEX idx_exercises_search_vector ON exercises USING gin(search_vector);
  ```
- Doctrine extension: `stof/doctrine-extensions-bundle`

#### 2. Brak limitów na liczbie zwracanych wyników
**Problem**: Zwrócenie 10,000+ ćwiczeń w jednym response
**Rozwiązanie**: 
- Default limit 100
- Pagination (offset/limit lub cursor-based)
- Frontend infinite scroll

#### 3. Serializacja dużych obiektów
**Problem**: Doctrine hydratuje pełne obiekty z relacjami
**Rozwiązanie**:
- Użycie Query Result DTO zamiast Entity:
  ```php
  $qb->select('NEW ' . ExerciseDto::class . '(e.id, e.name, mc.id, mc.namePl)')
     ->from(Exercise::class, 'e')
     ->join('e.muscleCategory', 'mc');
  ```

### Monitoring wydajności

**Metryki do śledzenia:**
1. Czas odpowiedzi (target: <200ms for GET /exercises)
2. Liczba wykonanych queries per request (target: ≤2)
3. Cache hit ratio (target: >80% po wdrożeniu cache)
4. Liczba zwracanych wyników (monitoring peak values)

**Narzędzia:**
- Symfony Profiler (development)
- Blackfire.io (performance profiling)
- New Relic / DataDog (production monitoring)
- Doctrine Query Logger

## 9. Etapy wdrożenia

### Krok 1: Utworzenie interfejsu repozytorium Exercise
**Ścieżka:** `src/Domain/Repository/ExerciseRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Exercise;

interface ExerciseRepositoryInterface
{
    /**
     * @return array<int, Exercise>
     */
    public function findAll(): array;
    
    public function findById(string $id): ?Exercise;
    
    /**
     * @return array<int, Exercise>
     */
    public function findByFilters(?string $muscleCategoryId, ?string $search): array;
}
```

**Testy:** Brak (to interface)

---

### Krok 2: Implementacja repozytorium Exercise
**Ścieżka:** `src/Infrastructure/Repository/ExerciseRepository.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Exercise;
use App\Domain\Repository\ExerciseRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Exercise>
 */
final class ExerciseRepository extends ServiceEntityRepository implements ExerciseRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exercise::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.muscleCategory', 'mc')
            ->addSelect('mc')
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findById(string $id): ?Exercise
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.muscleCategory', 'mc')
            ->addSelect('mc')
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByFilters(?string $muscleCategoryId, ?string $search): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.muscleCategory', 'mc')
            ->addSelect('mc');

        if ($muscleCategoryId !== null) {
            $qb->andWhere('mc.id = :muscleCategoryId')
               ->setParameter('muscleCategoryId', $muscleCategoryId);
        }

        if ($search !== null && trim($search) !== '') {
            $searchTerm = '%' . strtolower(trim($search)) . '%';
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(e.name)', ':search'),
                    $qb->expr()->like('LOWER(e.nameEn)', ':search')
                )
            )->setParameter('search', $searchTerm);
        }

        return $qb->orderBy('e.name', 'ASC')
                  ->getQuery()
                  ->getResult();
    }
}
```

**Testy:** `tests/Unit/Infrastructure/Repository/ExerciseRepositoryTest.php`
- Test findAll() zwraca posortowane ćwiczenia
- Test findById() zwraca ćwiczenie lub null
- Test findByFilters() z muscleCategoryId
- Test findByFilters() z search term
- Test findByFilters() z oboma filtrami
- Test case-insensitive search

---

### Krok 3: Utworzenie DTO wyjściowego Exercise
**Ścieżka:** `src/Infrastructure/Api/Output/ExerciseDto.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

use App\Domain\Entity\Exercise;

final readonly class ExerciseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public MuscleCategoryDto $muscleCategory,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {}

    public static function fromEntity(Exercise $exercise, string $lang = 'pl'): self
    {
        // Wybierz nazwę w zależności od języka
        $name = match ($lang) {
            'en' => $exercise->getNameEn() ?? $exercise->getName(), // fallback do PL
            default => $exercise->getName(),
        };

        return new self(
            id: $exercise->getId(),
            name: $name,
            muscleCategory: MuscleCategoryDto::fromEntity($exercise->getMuscleCategory()),
            createdAt: $exercise->getCreatedAt(),
            updatedAt: $exercise->getUpdatedAt(),
        );
    }
}
```

**Testy:** `tests/Unit/Infrastructure/Api/Output/ExerciseDtoTest.php`
- Test fromEntity() z lang='pl'
- Test fromEntity() z lang='en'
- Test fromEntity() z lang='en' i null nameEn (fallback)

---

### Krok 4: Utworzenie DTO wejściowego dla query parameters
**Ścieżka:** `src/Infrastructure/Api/Input/GetExercisesQueryDto.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetExercisesQueryDto
{
    public function __construct(
        #[Assert\uuid4(message: 'Invalid muscle category ID format')]
        public ?string $muscleCategoryId = null,

        #[Assert\Length(max: 255, maxMessage: 'Search parameter cannot exceed {{ limit }} characters')]
        public ?string $search = null,

        #[Assert\NotBlank]
        #[Assert\Choice(
            choices: ['pl', 'en'],
            message: 'Language must be either "pl" or "en"'
        )]
        public string $lang = 'pl',
    ) {}
}
```

**Testy:** `tests/Unit/Infrastructure/Api/Input/GetExercisesQueryDtoTest.php`
- Test walidacji poprawnego uuid4
- Test walidacji niepoprawnego uuid4
- Test walidacji poprawnego języka
- Test walidacji niepoprawnego języka
- Test walidacji długości search

---

### Krok 5: Dodanie metody findById do MuscleCategoryRepositoryInterface
**Ścieżka:** `src/Domain/Repository/MuscleCategoryRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\MuscleCategory;

interface MuscleCategoryRepositoryInterface
{
    /**
     * @return array<int, MuscleCategory>
     */
    public function findAll(): array;
    
    public function findById(string $id): ?MuscleCategory; // ← NOWA METODA
}
```

**Ścieżka:** `src/Infrastructure/Repository/MuscleCategoryRepository.php`

```php
public function findById(string $id): ?MuscleCategory
{
    return $this->createQueryBuilder('mc')
        ->where('mc.id = :id')
        ->setParameter('id', $id)
        ->getQuery()
        ->getOneOrNullResult();
}
```

**Testy:** Dodać test do `tests/Unit/Infrastructure/Repository/MuscleCategoryRepositoryTest.php`

---

### Krok 6: Utworzenie kontrolera GET /exercises
**Ścieżka:** `src/Infrastructure/Controller/Exercise/GetExercisesController.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Exercise;

use App\Domain\Repository\ExerciseRepositoryInterface;
use App\Domain\Repository\MuscleCategoryRepositoryInterface;
use App\Infrastructure\Api\Input\GetExercisesQueryDto;
use App\Infrastructure\Api\Output\ExerciseDto;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\uuid4;

#[Route('/api/v1/exercises', name: 'get_exercises', methods: ['GET'])]
final class GetExercisesController extends AbstractController
{
    public function __construct(
        private readonly ExerciseRepositoryInterface $exerciseRepository,
        private readonly MuscleCategoryRepositoryInterface $muscleCategoryRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(
        #[MapQueryString] ?GetExercisesQueryDto $queryDto = null
    ): JsonResponse {
        try {
            // Default values if no query params provided
            $queryDto ??= new GetExercisesQueryDto();

            // Validate muscle category exists if provided
            if ($queryDto->muscleCategoryId !== null) {
                if (!uuid4::isValid($queryDto->muscleCategoryId)) {
                    return $this->json([
                        'error' => 'Invalid muscle category ID format',
                        'code' => 400,
                    ], Response::HTTP_BAD_REQUEST);
                }

                $muscleCategory = $this->muscleCategoryRepository->findById(
                    $queryDto->muscleCategoryId
                );

                if ($muscleCategory === null) {
                    $this->logger->warning('Muscle category not found', [
                        'muscleCategoryId' => $queryDto->muscleCategoryId,
                    ]);

                    return $this->json([
                        'error' => 'Muscle category not found',
                        'code' => 404,
                    ], Response::HTTP_NOT_FOUND);
                }
            }

            // Fetch exercises with filters
            $exercises = $this->exerciseRepository->findByFilters(
                muscleCategoryId: $queryDto->muscleCategoryId,
                search: $queryDto->search
            );

            // Transform to DTOs with language preference
            $exerciseDtos = array_map(
                fn($exercise) => ExerciseDto::fromEntity($exercise, $queryDto->lang),
                $exercises
            );

            return $this->json($exerciseDtos, Response::HTTP_OK);

        } catch (ConnectionException $e) {
            $this->logger->error('Database connection failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json([
                'error' => 'Service temporarily unavailable',
                'code' => 500,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } catch (ORMException $e) {
            $this->logger->error('ORM error occurred', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json([
                'error' => 'An error occurred while processing your request',
                'code' => 500,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } catch (\Throwable $e) {
            $this->logger->critical('Unexpected error occurred', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'endpoint' => 'GET /api/v1/exercises',
            ]);

            return $this->json([
                'error' => 'An unexpected error occurred',
                'code' => 500,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
```

**Testy:** `tests/Functional/Controller/Exercise/GetExercisesControllerTest.php`
- Test GET /exercises bez parametrów (200)
- Test GET /exercises z muscleCategoryId (200)
- Test GET /exercises z search (200)
- Test GET /exercises z oboma filtrami (200)
- Test GET /exercises z lang=en (200)
- Test GET /exercises z nieprawidłowym muscleCategoryId (404)
- Test GET /exercises z nieprawidłowym formatem uuid4 (400)
- Test GET /exercises bez tokena JWT (401)

---

### Krok 7: Utworzenie kontrolera GET /exercises/{id}
**Ścieżka:** `src/Infrastructure/Controller/Exercise/GetExerciseByIdController.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Exercise;

use App\Domain\Repository\ExerciseRepositoryInterface;
use App\Infrastructure\Api\Output\ExerciseDto;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\uuid4;

#[Route('/api/v1/exercises/{id}', name: 'get_exercise_by_id', methods: ['GET'])]
final class GetExerciseByIdController extends AbstractController
{
    public function __construct(
        private readonly ExerciseRepositoryInterface $exerciseRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            // Validate uuid4 format
            if (!uuid4::isValid($id)) {
                return $this->json([
                    'error' => 'Invalid exercise ID format',
                    'code' => 400,
                ], Response::HTTP_BAD_REQUEST);
            }

            // Find exercise
            $exercise = $this->exerciseRepository->findById($id);

            if ($exercise === null) {
                $this->logger->info('Exercise not found', ['id' => $id]);

                return $this->json([
                    'error' => 'Exercise not found',
                    'code' => 404,
                ], Response::HTTP_NOT_FOUND);
            }

            // Transform to DTO (default language: pl)
            $exerciseDto = ExerciseDto::fromEntity($exercise, lang: 'pl');

            return $this->json($exerciseDto, Response::HTTP_OK);

        } catch (ConnectionException $e) {
            $this->logger->error('Database connection failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json([
                'error' => 'Service temporarily unavailable',
                'code' => 500,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } catch (ORMException $e) {
            $this->logger->error('ORM error occurred', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json([
                'error' => 'An error occurred while processing your request',
                'code' => 500,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } catch (\Throwable $e) {
            $this->logger->critical('Unexpected error occurred', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'endpoint' => 'GET /api/v1/exercises/{id}',
            ]);

            return $this->json([
                'error' => 'An unexpected error occurred',
                'code' => 500,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
```

**Testy:** `tests/Functional/Controller/Exercise/GetExerciseByIdControllerTest.php`
- Test GET /exercises/{id} z prawidłowym ID (200)
- Test GET /exercises/{id} z nieistniejącym ID (404)
- Test GET /exercises/{id} z nieprawidłowym formatem uuid4 (400)
- Test GET /exercises/{id} bez tokena JWT (401)

---

### Krok 8: Konfiguracja security dla nowych endpointów
**Ścieżka:** `config/packages/security.yaml`

Sprawdzić czy istnieje:
```yaml
access_control:
    - { path: ^/api/v1/exercises, roles: IS_AUTHENTICATED_FULLY }
```

Jeśli nie ma takiego wpisu, dodać.

---

### Krok 9: Utworzenie testów jednostkowych

#### a) ExerciseRepositoryTest
**Ścieżka:** `tests/Unit/Infrastructure/Repository/ExerciseRepositoryTest.php`

Testy:
- `testFindAllReturnsExercisesSortedByName()`
- `testFindByIdReturnsExercise()`
- `testFindByIdReturnsNullWhenNotFound()`
- `testFindByFiltersWithMuscleCategoryId()`
- `testFindByFiltersWithSearchTerm()`
- `testFindByFiltersWithBothFilters()`
- `testFindByFiltersCaseInsensitiveSearch()`

#### b) ExerciseDtoTest
**Ścieżka:** `tests/Unit/Infrastructure/Api/Output/ExerciseDtoTest.php`

Testy:
- `testFromEntityWithPolishLanguage()`
- `testFromEntityWithEnglishLanguage()`
- `testFromEntityWithEnglishLanguageFallback()`

#### c) GetExercisesQueryDtoTest
**Ścieżka:** `tests/Unit/Infrastructure/Api/Input/GetExercisesQueryDtoTest.php`

Testy:
- `testValidMuscleCategoryIduuid4()`
- `testInvalidMuscleCategoryIduuid4()`
- `testValidLanguage()`
- `testInvalidLanguage()`
- `testSearchMaxLength()`

---

### Krok 10: Utworzenie testów funkcjonalnych

#### a) GetExercisesControllerTest
**Ścieżka:** `tests/Functional/Controller/Exercise/GetExercisesControllerTest.php`

Setup:
- Utworzenie użytkownika testowego
- Utworzenie kategorii mięśniowych
- Utworzenie przykładowych ćwiczeń
- Wygenerowanie tokena JWT

Testy:
- `testGetExercisesWithoutFiltersReturnsAllExercises()`
- `testGetExercisesWithMuscleCategoryFilter()`
- `testGetExercisesWithSearchFilter()`
- `testGetExercisesWithBothFilters()`
- `testGetExercisesWithEnglishLanguage()`
- `testGetExercisesWithInvalidMuscleCategoryIdReturns404()`
- `testGetExercisesWithInvaliduuid4FormatReturns400()`
- `testGetExercisesWithoutAuthenticationReturns401()`

#### b) GetExerciseByIdControllerTest
**Ścieżka:** `tests/Functional/Controller/Exercise/GetExerciseByIdControllerTest.php`

Testy:
- `testGetExerciseByIdReturnsExercise()`
- `testGetExerciseByIdWithNonExistentIdReturns404()`
- `testGetExerciseByIdWithInvaliduuid4FormatReturns400()`
- `testGetExerciseByIdWithoutAuthenticationReturns401()`

---

### Krok 11: Uruchomienie testów

```bash
# Uruchomienie wszystkich testów
docker exec workouttracker-php-1 php bin/phpunit

# Uruchomienie tylko testów jednostkowych
docker exec workouttracker-php-1 php bin/phpunit tests/Unit

# Uruchomienie tylko testów funkcjonalnych
docker exec workouttracker-php-1 php bin/phpunit tests/Functional

# Uruchomienie testów dla Exercise
docker exec workouttracker-php-1 php bin/phpunit tests/Functional/Controller/Exercise
docker exec workouttracker-php-1 php bin/phpunit tests/Unit/Infrastructure/Repository/ExerciseRepositoryTest.php
```

---

### Krok 12: Uruchomienie PHPStan

```bash
docker exec workouttracker-php-1 vendor/bin/phpstan analyse src tests --level=9
```

Naprawić wszystkie błędy zgłoszone przez PHPStan.

---

### Krok 13: Uruchomienie PHP CS Fixer

```bash
docker exec workouttracker-php-1 vendor/bin/php-cs-fixer fix src
docker exec workouttracker-php-1 vendor/bin/php-cs-fixer fix tests
```

---

### Krok 14: Testowanie manualne z Postman/curl

#### GET /exercises bez filtrów
```bash
curl -X GET "http://localhost:8000/api/v1/exercises" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### GET /exercises z filtrem muscleCategoryId
```bash
curl -X GET "http://localhost:8000/api/v1/exercises?muscleCategoryId=01HQYYYYYYYYYYYYYYYYYYYYYY" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### GET /exercises z wyszukiwaniem
```bash
curl -X GET "http://localhost:8000/api/v1/exercises?search=bench" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### GET /exercises z językiem angielskim
```bash
curl -X GET "http://localhost:8000/api/v1/exercises?lang=en" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### GET /exercises/{id}
```bash
curl -X GET "http://localhost:8000/api/v1/exercises/01HQXXXXXXXXXXXXXXXXXXXXXX" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

---

### Krok 15: Aktualizacja dokumentacji Swagger

**Ścieżka:** `docs/swagger.json`

Sprawdzić czy endpointy są poprawnie udokumentowane z:
- Schematami ExerciseDto
- Schematami błędów (Error)
- security: bearerAuth
- Wszystkimi parametrami i ich walidacją

---

### Krok 16: Code review checklist

✅ **Kod:**
- [ ] Wszystkie klasy oznaczone `final`
- [ ] Wszystkie klasy readonly gdzie to możliwe
- [ ] Constructor property promotion użyte
- [ ] Strict types `declare(strict_types=1);` w każdym pliku
- [ ] Named arguments użyte
- [ ] Match expression zamiast switch
- [ ] Brak public setterów w encjach

✅ **Architektura:**
- [ ] Repository interface w Domain
- [ ] Repository implementation w Infrastructure
- [ ] DTOs w Infrastructure/Api/Input i Output
- [ ] Controllers w Infrastructure/Controller
- [ ] Thin controllers (tylko routing + validation)
- [ ] Logika biznesowa w repository/services

✅ **Bezpieczeństwo:**
- [ ] JWT authentication wymagane
- [ ] Walidacja wszystkich inputów
- [ ] Parameterized queries (Doctrine)
- [ ] Brak logowania sensitive data
- [ ] uuid4 validation

✅ **Testy:**
- [ ] Code coverage >80%
- [ ] Unit tests dla repositories
- [ ] Unit tests dla DTOs
- [ ] Functional tests dla controllers
- [ ] Test cases dla error scenarios

✅ **Jakość:**
- [ ] PHPStan level 9 pass
- [ ] PHP CS Fixer pass
- [ ] Brak duplikacji kodu
- [ ] Descriptive variable/method names
- [ ] Proper error handling z logging

✅ **Dokumentacja:**
- [ ] Swagger documentation aktualna
- [ ] PHPDoc dla złożonych metod
- [ ] README updated (jeśli potrzeba)

---

## 10. Podsumowanie

### Utworzone pliki (8 nowych plików):

1. `src/Domain/Repository/ExerciseRepositoryInterface.php`
2. `src/Infrastructure/Repository/ExerciseRepository.php`
3. `src/Infrastructure/Api/Output/ExerciseDto.php`
4. `src/Infrastructure/Api/Input/GetExercisesQueryDto.php`
5. `src/Infrastructure/Controller/Exercise/GetExercisesController.php`
6. `src/Infrastructure/Controller/Exercise/GetExerciseByIdController.php`
7. `tests/Functional/Controller/Exercise/GetExercisesControllerTest.php`
8. `tests/Functional/Controller/Exercise/GetExerciseByIdControllerTest.php`

### Zmodyfikowane pliki (2):

1. `src/Domain/Repository/MuscleCategoryRepositoryInterface.php` - dodanie metody `findById()`
2. `src/Infrastructure/Repository/MuscleCategoryRepository.php` - implementacja `findById()`

### Dodatkowe pliki testowe (3):

1. `tests/Unit/Infrastructure/Repository/ExerciseRepositoryTest.php`
2. `tests/Unit/Infrastructure/Api/Output/ExerciseDtoTest.php`
3. `tests/Unit/Infrastructure/Api/Input/GetExercisesQueryDtoTest.php`

### Szacowany czas implementacji:
- **Krok 1-7 (kod produkcyjny)**: ~3-4 godziny
- **Krok 8-10 (testy)**: ~3-4 godziny
- **Krok 11-16 (review + dokumentacja)**: ~1-2 godziny
- **TOTAL**: ~7-10 godzin (1-1.5 dnia pracy)

### Priorytet implementacji:
1. **High**: Kroki 1-7 (podstawowa funkcjonalność)
2. **High**: Krok 8 (testy funkcjonalne)
3. **Medium**: Krok 9-10 (testy jednostkowe)
4. **Low**: Krok 11-16 (quality assurance)

