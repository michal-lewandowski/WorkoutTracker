# API Endpoint Implementation Plan: Exercise Progress Statistics

## 1. Przegląd punktu końcowego
Punkt końcowy `/statistics/exercise/{exerciseId}` służy do pobierania statystyk postępu dla konkretnego ćwiczenia. Zwraca on dane dotyczące maksymalnej wagi użytej podczas każdej sesji treningowej dla danego ćwiczenia w czasie. Pozwala to użytkownikom śledzić swój postęp w konkretnym ćwiczeniu na przestrzeni czasu.

## 2. Szczegóły żądania
- **Metoda HTTP:** GET
- **Struktura URL:** `/statistics/exercise/{exerciseId}`
- **Parametry:**
  - **Wymagane:**
    - `exerciseId` (path parameter) - UUID ćwiczenia
  - **Opcjonalne:**
    - `dateFrom` (query parameter) - data początkowa dla zakresu statystyk (format: date)
    - `dateTo` (query parameter) - data końcowa dla zakresu statystyk (format: date)
    - `limit` (query parameter) - maksymalna liczba punktów danych (minimum: 1, maksimum: 1000, domyślnie: 100)
- **Wymagania uwierzytelniania:** JWT token (Bearer Authentication)

## 3. Wykorzystywane typy

### DTOs (Data Transfer Objects)
```php
// DTO odpowiedzi
final readonly class ExerciseStatisticsDto
{
    /**
     * @param array<ExerciseStatisticsDataPointDto> $dataPoints
     */
    public function __construct(
        public string $exerciseId,
        public ExerciseSimpleDto $exercise,
        public array $dataPoints,
        public ?ExerciseStatisticsSummaryDto $summary,
    ) {}
}

final readonly class ExerciseSimpleDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $nameEn,
    ) {}
}

final readonly class ExerciseStatisticsDataPointDto
{
    public function __construct(
        public string $date,
        public string $sessionId,
        public float $maxWeightKg,
    ) {}
}

final readonly class ExerciseStatisticsSummaryDto
{
    public function __construct(
        public int $totalSessions,
        public float $personalRecord,
        public string $prDate,
        public float $firstWeight,
        public float $latestWeight,
        public float $progressPercentage,
    ) {}
}

// DTO parametrów zapytania
final readonly class ExerciseStatisticsQueryDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $exerciseId,
        
        #[Assert\Date]
        public ?string $dateFrom = null,
        
        #[Assert\Date]
        public ?string $dateTo = null,
        
        #[Assert\Range(min: 1, max: 1000)]
        public ?int $limit = 100,
    ) {}
}
```

### Command Models
```php
final readonly class GetExerciseStatisticsCommand
{
    public function __construct(
        public string $exerciseId,
        public string $userId,
        public ?\DateTimeImmutable $dateFrom = null,
        public ?\DateTimeImmutable $dateTo = null,
        public ?int $limit = 100,
    ) {}
}
```

## 4. Szczegóły odpowiedzi
- **Status 200 OK:**
  ```json
  {
    "exerciseId": "3a5b2509-e93b-4433-9520-dd51b1d6ef32",
    "exercise": {
      "id": "3a5b2509-e93b-4433-9520-dd51b1d6ef32",
      "name": "Wyciskanie sztangi leżąc",
      "nameEn": "Barbell Bench Press"
    },
    "dataPoints": [
      {
        "date": "2025-09-15",
        "sessionId": "3a5b2509-e93b-4433-9520-dd51b1d6ef32",
        "maxWeightKg": 70.0
      },
      {
        "date": "2025-09-22",
        "sessionId": "4b6c3610-f04c-5544-0631-ee62c2d7fe43",
        "maxWeightKg": 75.0
      }
    ],
    "summary": {
      "totalSessions": 3,
      "personalRecord": 75.0,
      "prDate": "2025-09-22",
      "firstWeight": 70.0,
      "latestWeight": 75.0,
      "progressPercentage": 7.14
    }
  }
  ```

- **Status 401 Unauthorized:**
  ```json
  {
    "message": "Brak autoryzacji"
  }
  ```

- **Status 404 Not Found:**
  ```json
  {
    "message": "Ćwiczenie nie zostało znalezione"
  }
  ```

## 5. Przepływ danych
1. Klient wysyła żądanie GET z parametrem ścieżki `exerciseId` i opcjonalnymi parametrami zapytania.
2. Controller waliduje parametry żądania i mapuje je na DTO parametrów zapytania (`ExerciseStatisticsQueryDto`).
3. Controller pobiera identyfikator zalogowanego użytkownika z tokenu JWT.
4. Controller tworzy obiekt Command (`GetExerciseStatisticsCommand`) na podstawie DTO parametrów i ID użytkownika.
5. Command Handler przetwarza komendę:
   - Sprawdza czy ćwiczenie istnieje w bazie danych.
   - Pobiera wszystkie sesje treningowe użytkownika z danym ćwiczeniem w określonym zakresie dat.
   - Dla każdej sesji wylicza maksymalną wagę użytą dla danego ćwiczenia.
   - Tworzy punkty danych z datą sesji, ID sesji i maksymalną wagą.
   - Oblicza podsumowanie statystyk (rekord personalny, procent postępu, itp.).
   - Tworzy DTO odpowiedzi.
6. Controller zwraca DTO odpowiedzi jako odpowiedź JSON.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie:** Wymagany token JWT (Bearer Authentication).
- **Autoryzacja:** Użytkownik może przeglądać tylko swoje własne statystyki.
- **Walidacja danych wejściowych:**
  - Walidacja ID ćwiczenia jako prawidłowy UUID.
  - Walidacja parametrów daty jako prawidłowe formaty daty.
  - Walidacja limitu jako liczby całkowitej między 1 a 1000.
- **Sanityzacja danych wyjściowych:** Dane wyjściowe są serializowane z wykorzystaniem DTO, co zapobiega ujawnieniu wrażliwych informacji.

## 7. Obsługa błędów
- **401 Unauthorized:** Gdy użytkownik nie jest zalogowany lub token jest nieprawidłowy.
- **404 Not Found:**
  - Gdy ćwiczenie o podanym ID nie istnieje.
  - Gdy użytkownik nie ma żadnych sesji z danym ćwiczeniem.
- **400 Bad Request:**
  - Gdy parametry zapytania są nieprawidłowego formatu.
  - Gdy limit jest poza dozwolonym zakresem.
- **500 Internal Server Error:** W przypadku nieoczekiwanych błędów podczas przetwarzania.

## 8. Rozważania dotyczące wydajności
- **Indeksowanie bazy danych:** Upewnij się, że kolumny używane w zapytaniach (exercise_id, user_id, date) są zindeksowane.
- **Paginacja:** Parametr `limit` ogranicza liczbę zwracanych punktów danych.
- **Caching:** Rozważ cachowanie wyników dla popularnych ćwiczeń i użytkowników.
- **Optymalizacja zapytań:** Użyj złożonych zapytań SQL/DQL zamiast wielu małych zapytań.
- **Selektywne pobieranie danych:** Pobieraj tylko niezbędne dane, używając projekcji w zapytaniach.

## 9. Etapy wdrożenia

### 1. Przygotowanie DTOs
1. Stwórz katalog `src/Application/DTO/Statistics` jeśli nie istnieje.
2. Zaimplementuj wszystkie wymagane DTOs:
   - `ExerciseStatisticsDto`
   - `ExerciseSimpleDto`
   - `ExerciseStatisticsDataPointDto`
   - `ExerciseStatisticsSummaryDto`
   - `ExerciseStatisticsQueryDto`

### 2. Przygotowanie Command i Handler
1. Stwórz katalog `src/Application/Command/Statistics` jeśli nie istnieje.
2. Zaimplementuj Command `GetExerciseStatisticsCommand`.
3. Zaimplementuj Handler `GetExerciseStatisticsCommandHandler`:
   ```php
   final class GetExerciseStatisticsCommandHandler
   {
       public function __construct(
           private readonly ExerciseRepositoryInterface $exerciseRepository,
           private readonly WorkoutSessionRepositoryInterface $sessionRepository,
           private readonly WorkoutExerciseRepositoryInterface $workoutExerciseRepository,
       ) {}
       
       public function handle(GetExerciseStatisticsCommand $command): ExerciseStatisticsDto
       {
           // Implementacja logiki
       }
   }
   ```

### 3. Serwisy Domenowe
1. Jeśli potrzeba, stwórz serwis `StatisticsCalculator` w `src/Domain/Service`:
   ```php
   final class StatisticsCalculator
   {
       public function calculateProgressPercentage(float $firstWeight, float $latestWeight): float
       {
           // Implementacja logiki
       }
       
       // Inne metody pomocnicze
   }
   ```

### 4. Rozszerzenie Repozytoriów
1. Dodaj metody do repozytoriów w `src/Domain/Repository`:
   ```php
   interface WorkoutExerciseRepositoryInterface
   {
       // Istniejące metody...
       
       /**
        * @return array<array{date: \DateTimeImmutable, sessionId: string, maxWeightKg: float}>
        */
       public function findMaxWeightPerSessionByExerciseAndUser(
           string $exerciseId, 
           string $userId, 
           ?\DateTimeImmutable $dateFrom = null, 
           ?\DateTimeImmutable $dateTo = null, 
           ?int $limit = 100
       ): array;
   }
   ```

2. Implementuj nowe metody w `src/Infrastructure/Repository`:
   ```php
   final class WorkoutExerciseRepository implements WorkoutExerciseRepositoryInterface
   {
       // Istniejące metody...
       
       public function findMaxWeightPerSessionByExerciseAndUser(
           string $exerciseId, 
           string $userId, 
           ?\DateTimeImmutable $dateFrom = null, 
           ?\DateTimeImmutable $dateTo = null, 
           ?int $limit = 100
       ): array {
           // Implementacja z wykorzystaniem DQL/QueryBuilder
       }
   }
   ```

### 5. Implementacja Controllera
1. Stwórz controller w `src/Infrastructure/Controller/StatisticsController.php`:
   ```php
   final class StatisticsController extends AbstractController
   {
       #[Route('/statistics/exercise/{exerciseId}', methods: ['GET'])]
       public function getExerciseStatistics(
           string $exerciseId,
           Request $request,
           GetExerciseStatisticsCommandHandler $handler,
       ): JsonResponse {
           // Implementacja
       }
   }
   ```