# API Endpoint Implementation Plan: WorkoutSessions

## 1. Przegląd punktu końcowego

Implementacja dwóch punktów końcowych REST API dla zarządzania sesjami treningowymi:

1. `GET /workout-sessions` - pobieranie paginowanej listy sesji treningowych użytkownika z możliwością filtrowania i sortowania
2. `POST /workout-sessions` - tworzenie nowej sesji treningowej

Oba punkty końcowe będą wymagały uwierzytelnienia (bearer token JWT), zapewniając, że użytkownicy mają dostęp tylko do własnych sesji treningowych.

## 2. Szczegóły żądania

### GET /workout-sessions

- **Metoda HTTP**: GET
- **Struktura URL**: `/workout-sessions`
- **Uwierzytelnianie**: Bearer token JWT
- **Parametry**:
  - **Opcjonalne**:
    - `limit` (int): Liczba wyników na stronę (min: 1, max: 100, domyślnie: 50)
    - `offset` (int): Offset paginacji (min: 0, domyślnie: 0)
    - `dateFrom` (string, format: date): Filtrowanie sesji od daty
    - `dateTo` (string, format: date): Filtrowanie sesji do daty
    - `sortBy` (string, enum: ["date", "createdAt"]): Pole sortowania (domyślnie: "date")
    - `sortOrder` (string, enum: ["asc", "desc"]): Kolejność sortowania (domyślnie: "desc")

### POST /workout-sessions

- **Metoda HTTP**: POST
- **Struktura URL**: `/workout-sessions`
- **Uwierzytelnianie**: Bearer token JWT
- **Request Body**:
  ```json
  {
    "date": "2025-10-11", // Wymagane, format: data
    "name": "Trening nóg", // Opcjonalne
    "notes": "Skupienie na przysiadach" // Opcjonalne
  }
  ```

## 3. Wykorzystywane typy

### DTOs wejściowe

1. **GetWorkoutSessionsQueryDto**
   ```php
   final readonly class GetWorkoutSessionsQueryDto
   {
       public function __construct(
           #[Assert\Range(min: 1, max: 100)]
           public int $limit = 50,
           
           #[Assert\GreaterThanOrEqual(0)]
           public int $offset = 0,
           
           #[Assert\Date]
           public ?string $dateFrom = null,
           
           #[Assert\Date]
           public ?string $dateTo = null,
           
           #[Assert\Choice(choices: ["date", "createdAt"])]
           public string $sortBy = "date",
           
           #[Assert\Choice(choices: ["asc", "desc"])]
           public string $sortOrder = "desc"
       ) {}
   }
   ```

2. **CreateWorkoutSessionRequestDto**
   ```php
   final readonly class CreateWorkoutSessionRequestDto
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

1. **WorkoutSessionDto**
   ```php
   final readonly class WorkoutSessionDto
   {
       public function __construct(
           public string $id,
           public string $date,
           public ?string $name,
           public ?string $notes,
           public \DateTimeImmutable $createdAt,
           public int $exerciseCount
       ) {}
   }
   ```

2. **WorkoutSessionListDto**
   ```php
   final readonly class WorkoutSessionListDto
   {
       /**
        * @param array<WorkoutSessionDto> $items
        */
       public function __construct(
           public array $items,
           public int $total,
           public int $limit,
           public int $offset
       ) {}
   }
   ```

3. **WorkoutSessionDetailDto**
   ```php
   final readonly class WorkoutSessionDetailDto
   {
       public function __construct(
           public string $id,
           public string $date,
           public ?string $name,
           public ?string $notes,
           public \DateTimeImmutable $createdAt,
           public \DateTimeImmutable $updatedAt,
           public array $exercises = []
       ) {}
   }
   ```

### Command Modele

1. **CreateWorkoutSessionCommand**
   ```php
   final readonly class CreateWorkoutSessionCommand
   {
       public function __construct(
           public string $userId,
           public \DateTimeImmutable $date,
           public ?string $name,
           public ?string $notes
       ) {}
   }
   ```

### Repository Interface

1. **WorkoutSessionRepositoryInterface**
   ```php
   interface WorkoutSessionRepositoryInterface
   {
       public function save(WorkoutSession $workoutSession): void;
       public function findById(string $id): ?WorkoutSession;
       public function findByUserIdPaginated(
           string $userId, 
           int $limit, 
           int $offset, 
           ?\DateTimeImmutable $dateFrom = null,
           ?\DateTimeImmutable $dateTo = null,
           string $sortBy = 'date',
           string $sortOrder = 'desc'
       ): array;
       public function countByUserId(
           string $userId,
           ?\DateTimeImmutable $dateFrom = null,
           ?\DateTimeImmutable $dateTo = null
       ): int;
   }
   ```

## 4. Szczegóły odpowiedzi

### GET /workout-sessions

- **Sukces (200 OK)**:
  ```json
  {
    "items": [
      {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "date": "2025-10-11",
        "name": "Trening nóg",
        "notes": "Skupienie na przysiadach",
        "createdAt": "2025-10-11T19:42:51+00:00",
        "exerciseCount": 5
      },
      // ...więcej elementów
    ],
    "total": 42,
    "limit": 10,
    "offset": 0
  }
  ```

- **Błąd uwierzytelnienia (401 Unauthorized)**:
  ```json
  {
    "message": "JWT Token not found",
    "code": 401
  }
  ```

### POST /workout-sessions

- **Sukces (201 Created)**:
  ```json
  {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "date": "2025-10-11",
    "name": "Trening nóg",
    "notes": "Skupienie na przysiadach",
    "createdAt": "2025-10-11T19:42:51+00:00",
    "updatedAt": "2025-10-11T19:42:51+00:00",
    "exercises": []
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

- **Błąd uwierzytelnienia (401 Unauthorized)**:
  ```json
  {
    "message": "JWT Token not found",
    "code": 401
  }
  ```

## 5. Przepływ danych

### GET /workout-sessions

1. Kontroler odbiera żądanie HTTP z parametrami zapytania
2. Parametry są mapowane na GetWorkoutSessionsQueryDto przy użyciu #[MapQueryString]
3. Kontroler pobiera zalogowanego użytkownika z SecuritySystem
4. Kontroler przekazuje dto i userId do serwisu WorkoutSessionService
5. Serwis wywołuje repository dla pobrania danych i liczby rekordów
6. Repository używa Doctrine QueryBuilder do wykonania zapytania z filtrami
7. Serwis mapuje encje na DTOs
8. Kontroler zwraca odpowiedź HTTP z danymi

### POST /workout-sessions

1. Kontroler odbiera żądanie HTTP z body
2. Body jest mapowane na CreateWorkoutSessionRequestDto przy użyciu #[MapRequestPayload]
3. Kontroler pobiera zalogowanego użytkownika z SecuritySystem
4. Kontroler tworzy CreateWorkoutSessionCommand i przekazuje do CreateWorkoutSessionHandler
5. Handler weryfikuje dane i tworzy nową encję WorkoutSession
6. Handler zapisuje encję przez repository
7. Kontroler mapuje encję na WorkoutSessionDetailDto
8. Kontroler zwraca odpowiedź HTTP 201 z danymi

## 6. Względy bezpieczeństwa

1. **Uwierzytelnianie**:
   - Wymaga poprawnego tokenu JWT
   - Wykorzystanie usługi LexikJWTAuthenticationBundle

2. **Autoryzacja**:
   - Zapewnienie, że użytkownicy mogą zobaczyć i tworzyć tylko własne sesje treningowe
   - Kontrola dostępu w kontrolerze poprzez sprawdzanie userId

3. **Walidacja danych**:
   - Szczegółowa walidacja wszystkich parametrów wejściowych przy użyciu Symfony Validator
   - Obsługa błędów walidacji przez ValidationExceptionListener

4. **Zabezpieczenia bazy danych**:
   - Używanie UUID zamiast sekwencyjnych ID
   - Parameteryzowane zapytania przez Doctrine (ochrona przed SQL injection)
   - Indeksy na kluczach wyszukiwania (user_id, date)

5. **Zapobieganie atakom**:
   - Limit rozmiaru żądania
   - Możliwość dodania rate-limitingu dla endpointu

## 7. Obsługa błędów

1. **Nieprawidłowe dane wejściowe**:
   - Status: 400 Bad Request
   - Szczegółowe komunikaty o błędach walidacji
   - Używanie ValidationExceptionListener dla spójnego formatowania błędów

2. **Błąd uwierzytelnienia**:
   - Status: 401 Unauthorized
   - Standardowy format błędu z LexikJWTAuthenticationBundle

3. **Błędy wewnętrzne**:
   - Status: 500 Internal Server Error
   - Ogólny komunikat błędu (bez ujawniania szczegółów implementacji)
   - Logowanie szczegółów błędu przez Monolog

4. **Nielegalne wartości parametrów**:
   - Automatyczna walidacja przez Symfony Validator
   - Zwraca 400 Bad Request z szczegółami błędu

## 8. Rozważania dotyczące wydajności

1. **Paginacja**:
   - Limit maksymalnej liczby rekordów (100)
   - Używanie OFFSET/LIMIT w zapytaniach SQL
   - Indeksy na kluczach filtrowania i sortowania

2. **Filtrowanie i sortowanie**:
   - Indeksy bazy danych na polach filtrowania (user_id, date)
   - Efektywne zapytania przez Doctrine QueryBuilder

3. **Optymalizacja zapytań**:
   - Pobieranie tylko niezbędnych pól
   - Agregacja liczby ćwiczeń w jednym zapytaniu
   - Separacja zapytań SELECT i COUNT

## 9. Etapy wdrożenia

### 1. Utworzenie interfejsów

1. Zdefiniować interfejs WorkoutSessionRepositoryInterface w Domain/Repository/

### 2. Utworzenie DTOs

1. Stworzyć GetWorkoutSessionsQueryDto w Infrastructure/Api/Input/
2. Stworzyć CreateWorkoutSessionRequestDto w Infrastructure/Api/Input/
3. Stworzyć WorkoutSessionDto, WorkoutSessionListDto i WorkoutSessionDetailDto w Infrastructure/Api/Output/

### 3. Utworzenie Command Models

1. Stworzyć CreateWorkoutSessionCommand w Application/Command/WorkoutSession/

### 4. Implementacja Repository

1. Stworzyć WorkoutSessionRepository implementujący interfejs w Infrastructure/Repository/

### 5. Implementacja Command Handler

1. Stworzyć CreateWorkoutSessionHandler w Application/Command/WorkoutSession/

### 6. Implementacja kontrolerów

1. Stworzyć GetWorkoutSessionsController w Infrastructure/Controller/WorkoutSession/
2. Stworzyć CreateWorkoutSessionController w Infrastructure/Controller/WorkoutSession/

### 7. Konfiguracja zależności

1. Zaktualizować services.yaml dla nowych serwisów i kontrolerów

### 8. Testy

1. Stworzyć testy jednostkowe dla Command Handlerów
2. Stworzyć testy funkcjonalne dla kontrolerów
3. Stworzyć testy integracyjne dla repozytorium

### 9. Dokumentacja

1. Zaktualizować dokumentację Swagger/OpenAPI (swagger.json)

### 10. Wdrożenie

1. Uruchomić migrację jeśli potrzebna
2. Wdrożyć na środowisko testowe
3. Przeprowadzić testy end-to-end
4. Wdrożyć na środowisko produkcyjne
