# API Endpoint Implementation Plan: GET /api/v1/muscle-categories

## 1. Przegląd punktu końcowego

Ten endpoint jest prostym publicznym endpointem typu read-only, który zwraca listę wszystkich dostępnych kategorii mięśni w systemie (oczekiwane 6 kategorii). Endpoint nie wymaga autoryzacji i służy do dostarczenia podstawowej referencyjnej informacji dla frontendu, która będzie używana przy wyświetlaniu i filtrowaniu ćwiczeń.

**Cel**: Udostępnienie listy wszystkich kategorii mięśni dla aplikacji klienckiej.

**Charakterystyka**:
- Publiczny endpoint (brak wymaganej autoryzacji)
- Tylko odczyt (GET)
- Stała lista danych (6 kategorii mięśni)
- Możliwość cache'owania po stronie klienta
- Niska częstotliwość zmian danych

## 2. Szczegóły żądania

### HTTP Method
`GET`

### Struktura URL
```
/api/v1/muscle-categories
```

### Parametry
- **Wymagane**: Brak
- **Opcjonalne**: Brak
- **Query Parameters**: Brak
- **Path Parameters**: Brak

### Request Headers
```
Accept: application/json
```

### Request Body
Brak (metoda GET)

### Przykład żądania
```bash
curl -X GET "http://localhost:8000/api/v1/muscle-categories" \
  -H "Accept: application/json"
```

## 3. Wykorzystywane typy

### 3.1 Output DTO

**Plik**: `src/Infrastructure/Api/Output/MuscleCategoryDto.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

use App\Domain\Entity\MuscleCategory;

final readonly class MuscleCategoryDto
{
    public function __construct(
        public string $id,
        public string $namePl,
        public string $nameEn,
        public \DateTimeImmutable $createdAt,
    ) {}
    
    public static function fromEntity(MuscleCategory $muscleCategory): self
    {
        return new self(
            id: $muscleCategory->getId(),
            namePl: $muscleCategory->getNamePl(),
            nameEn: $muscleCategory->getNameEn(),
            createdAt: $muscleCategory->getCreatedAt(),
        );
    }
}
```

**Pola DTO**:
- `id` (string): Unikalny identyfikator ULID kategorii
- `namePl` (string): Nazwa kategorii po polsku
- `nameEn` (string): Nazwa kategorii po angielsku
- `createdAt` (DateTimeImmutable): Data utworzenia kategorii

### 3.2 Repository Interface

**Plik**: `src/Domain/Repository/MuscleCategoryRepositoryInterface.php`

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
}
```

### 3.3 Repository Implementation

**Plik**: `src/Infrastructure/Repository/MuscleCategoryRepository.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\MuscleCategory;
use App\Domain\Repository\MuscleCategoryRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MuscleCategory>
 */
final class MuscleCategoryRepository extends ServiceEntityRepository implements MuscleCategoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MuscleCategory::class);
    }

    /**
     * @return array<int, MuscleCategory>
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('mc')
            ->orderBy('mc.namePl', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

### 3.4 Controller

**Plik**: `src/Infrastructure/Controller/MuscleCategory/GetMuscleCategoriesController.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MuscleCategory;

use App\Domain\Repository\MuscleCategoryRepositoryInterface;
use App\Infrastructure\Api\Output\MuscleCategoryDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/muscle-categories', name: 'get_muscle_categories', methods: ['GET'])]
final class GetMuscleCategoriesController extends AbstractController
{
    public function __construct(
        private readonly MuscleCategoryRepositoryInterface $muscleCategoryRepository,
    ) {}

    public function __invoke(): JsonResponse
    {
        $muscleCategories = $this->muscleCategoryRepository->findAll();
        
        $muscleCategoryDtos = array_map(
            fn($category) => MuscleCategoryDto::fromEntity($category),
            $muscleCategories
        );

        return $this->json($muscleCategoryDtos, Response::HTTP_OK);
    }
}
```

## 4. Szczegóły odpowiedzi

### 4.1 Sukces - 200 OK

**Status Code**: `200 OK`

**Headers**:
```
Content-Type: application/json
```

**Response Body**:
```json
[
  {
    "id": "01HN8W5ZQXK9J2V4M6P8R7T3D5",
    "namePl": "Klatka piersiowa",
    "nameEn": "Chest",
    "createdAt": "2024-10-10T18:11:37+00:00"
  },
  {
    "id": "01HN8W5ZQYB4N7M9K1J3H5G8F6",
    "namePl": "Plecy",
    "nameEn": "Back",
    "createdAt": "2024-10-10T18:11:37+00:00"
  },
  {
    "id": "01HN8W5ZQZC8M3K5J7H9G2F4D1",
    "namePl": "Nogi",
    "nameEn": "Legs",
    "createdAt": "2024-10-10T18:11:37+00:00"
  },
  {
    "id": "01HN8W5ZR0D1N4M7K9J2H5G8F3",
    "namePl": "Barki",
    "nameEn": "Shoulders",
    "createdAt": "2024-10-10T18:11:37+00:00"
  },
  {
    "id": "01HN8W5ZR1E5K8M2J4H7G9F1D3",
    "namePl": "Biceps",
    "nameEn": "Biceps",
    "createdAt": "2024-10-10T18:11:37+00:00"
  },
  {
    "id": "01HN8W5ZR2F9M1K3J5H8G4D2F7",
    "namePl": "Triceps",
    "nameEn": "Triceps",
    "createdAt": "2024-10-10T18:11:37+00:00"
  }
]
```

**Opis**:
- Zwraca tablicę obiektów reprezentujących wszystkie kategorie mięśni
- Tablica jest posortowana alfabetycznie według polskiej nazwy
- Każdy obiekt zawiera pełne informacje o kategorii
- Pusta tablica `[]` jeśli brak kategorii w bazie (nie powinno się zdarzyć w produkcji)

### 4.2 Błąd serwera - 500 Internal Server Error

**Status Code**: `500 Internal Server Error`

**Response Body**:
```json
{
  "error": "An error occurred while fetching muscle categories",
  "code": 500
}
```

**Kiedy występuje**:
- Błąd połączenia z bazą danych
- Błąd Doctrine ORM
- Nieoczekiwany błąd aplikacji

## 5. Przepływ danych

### 5.1 Diagram przepływu

```
1. HTTP Request (GET /api/v1/muscle-categories)
   ↓
2. Symfony Router
   ↓
3. GetMuscleCategoriesController::__invoke()
   ↓
4. MuscleCategoryRepositoryInterface::findAll()
   ↓
5. Doctrine Query Builder
   ↓
6. PostgreSQL Database Query
   SELECT * FROM muscle_categories ORDER BY name_pl ASC
   ↓
7. Doctrine Hydration → MuscleCategory[] entities
   ↓
8. Array mapping: MuscleCategory → MuscleCategoryDto
   ↓
9. Symfony Serializer (JSON encoding)
   ↓
10. JsonResponse (200 OK)
   ↓
11. HTTP Response
```

### 5.2 Szczegółowy opis przepływu

**Krok 1-2: Routing**
- Symfony router dopasowuje URL `/api/v1/muscle-categories` z metodą GET
- Kieruje request do `GetMuscleCategoriesController`

**Krok 3: Controller Invocation**
- Controller zostaje wywołany jako invokable (`__invoke()`)
- Dependency Injection wstrzykuje `MuscleCategoryRepositoryInterface`

**Krok 4-6: Database Query**
- Repository wywołuje `findAll()` z orderem po `namePl`
- Doctrine Query Builder buduje SQL query
- Query jest wykonywane na PostgreSQL

**Krok 7: Hydration**
- Doctrine hydruje wyniki SQL do tablicy entity `MuscleCategory[]`
- Każda entity jest readonly i immutable

**Krok 8: DTO Transformation**
- Za pomocą `array_map` każda entity jest transformowana do DTO
- `MuscleCategoryDto::fromEntity()` tworzy DTO z pól entity

**Krok 9-11: Response**
- `$this->json()` serializuje tablicę DTOs do JSON
- Ustawia header `Content-Type: application/json`
- Zwraca `JsonResponse` ze statusem 200

### 5.3 Interakcje z bazą danych

**Tabela**: `muscle_categories`

**Query**:
```sql
SELECT 
    mc.id,
    mc.name_pl,
    mc.name_en,
    mc.created_at
FROM muscle_categories mc
ORDER BY mc.name_pl ASC
```

**Indeksy wykorzystywane**:
- Możliwe użycie indeksu na `name_pl` dla sortowania

**Typ query**: Simple SELECT, full table scan (akceptowalne dla małej tabeli)

## 6. Względy bezpieczeństwa

### 6.1 Autoryzacja i Autentykacja

**Status**: Brak autoryzacji

**Uzasadnienie**:
- Publiczny endpoint - informacje o kategoriach mięśni nie są wrażliwe
- Dane read-only bez możliwości modyfikacji
- Niezbędne dla działania frontendu przed logowaniem użytkownika
- Standardowa praktyka dla danych referencyjnych/słownikowych

**Ryzyko**: NISKIE

### 6.2 Rate Limiting

**Rekomendacja**: Implementacja rate limiting

**Parametry**:
- Limit: 60 requestów na minutę na IP
- Burst: 10 requestów na sekundę
- Window: Sliding window 60s

**Implementacja**:
```yaml
# config/packages/rate_limiter.yaml
framework:
    rate_limiter:
        muscle_categories_api:
            policy: 'sliding_window'
            limit: 60
            interval: '60 seconds'
```

**Zastosowanie w kontrolerze**:
```php
#[RateLimit(limiter: 'muscle_categories_api')]
```

**Uzasadnienie**: Ochrona przed nadużyciami i DDoS, nawet dla publicznych endpointów

### 6.3 CORS (Cross-Origin Resource Sharing)

**Wymagane headers**:
```
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Methods: GET
Access-Control-Allow-Headers: Content-Type, Accept
```

**Konfiguracja** (w `config/packages/nelmio_cors.yaml`):
```yaml
nelmio_cors:
    paths:
        '^/api/v1/muscle-categories':
            allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
            allow_methods: ['GET']
            allow_headers: ['Content-Type', 'Accept']
            max_age: 3600
```

### 6.4 Walidacja danych wyjściowych

- Doctrine entity validation zapewnia integralność danych z bazy
- DTO readonly properties zapewniają niemutowalność
- Typed properties w PHP 8.4 zapewniają type safety

### 6.5 SQL Injection

**Ryzyko**: BRAK

**Ochrona**:
- Doctrine ORM używa prepared statements
- QueryBuilder escapuje wartości
- Brak user input w query

### 6.6 Information Disclosure

**Ryzyko**: MINIMALNE

**Środki ostrożności**:
- Nie wyświetlać stack traces w production
- Generyczne komunikaty błędów 500
- Logowanie szczegółów błędów tylko po stronie serwera

### 6.7 Cache Poisoning

**Ochrona**:
- Brak user input = brak możliwości cache poisoning
- Walidacja headers `Accept: application/json`

## 7. Obsługa błędów

### 7.1 Sukces - 200 OK

**Warunek**: Pomyślne pobranie kategorii z bazy danych

**Response**:
```json
[
  {
    "id": "01HN8W5ZQXK9J2V4M6P8R7T3D5",
    "namePl": "Klatka piersiowa",
    "nameEn": "Chest",
    "createdAt": "2024-10-10T18:11:37+00:00"
  }
]
```

**Akcja**: Brak dodatkowych działań

### 7.2 Pusta lista - 200 OK

**Warunek**: Brak kategorii w bazie danych

**Response**:
```json
[]
```

**Akcja**:
- Zwrócić pustą tablicę
- Logować WARNING - to nie powinno się zdarzyć w normalnej sytuacji
- Alert dla zespołu ops

**Logging**:
```php
$this->logger->warning('No muscle categories found in database');
```

### 7.3 Database Connection Error - 500

**Warunek**: Błąd połączenia z PostgreSQL

**Response**:
```json
{
  "error": "Service temporarily unavailable",
  "code": 500
}
```

**Akcja**:
- Catch `Doctrine\DBAL\Exception\ConnectionException`
- Logować ERROR z pełnym stack trace
- Zwrócić generyczny komunikat
- Alert dla zespołu ops

**Kod**:
```php
try {
    $muscleCategories = $this->muscleCategoryRepository->findAll();
} catch (ConnectionException $e) {
    $this->logger->error('Database connection failed', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    return $this->json([
        'error' => 'Service temporarily unavailable',
        'code' => 500
    ], Response::HTTP_INTERNAL_SERVER_ERROR);
}
```

### 7.4 Doctrine/ORM Error - 500

**Warunek**: Błąd Doctrine (hydration, mapping, etc.)

**Response**:
```json
{
  "error": "An error occurred while processing your request",
  "code": 500
}
```

**Akcja**:
- Catch `Doctrine\ORM\ORMException`
- Logować ERROR z kontekstem
- Zwrócić generyczny komunikat

### 7.5 Unexpected Error - 500

**Warunek**: Nieoczekiwany błąd aplikacji

**Response**:
```json
{
  "error": "An unexpected error occurred",
  "code": 500
}
```

**Akcja**:
- Catch `\Throwable`
- Logować CRITICAL
- Alert dla zespołu
- Zwrócić generyczny komunikat

### 7.6 Invalid Content Type - 406 Not Acceptable

**Warunek**: Klient żąda innego formatu niż JSON

**Response**:
```json
{
  "error": "Only application/json content type is supported",
  "code": 406
}
```

**Implementacja**: Symfony automatycznie obsługuje to przez Content Negotiation

### 7.7 Method Not Allowed - 405

**Warunek**: Request z metodą inną niż GET (POST, PUT, DELETE, etc.)

**Response**:
```json
{
  "error": "Method Not Allowed",
  "code": 405
}
```

**Headers**:
```
Allow: GET
```

**Implementacja**: Symfony router automatycznie obsługuje

### 7.8 Tabela błędów

| Kod | Nazwa | Warunek | Komunikat | Logging Level | Alert |
|-----|-------|---------|-----------|---------------|-------|
| 200 | OK | Sukces | Array z kategoriami | INFO | Nie |
| 200 | OK (Empty) | Brak kategorii | Pusta tablica [] | WARNING | Tak |
| 405 | Method Not Allowed | Nieprawidłowa metoda HTTP | "Method Not Allowed" | INFO | Nie |
| 406 | Not Acceptable | Nieprawidłowy Accept header | "Only application/json supported" | INFO | Nie |
| 500 | Internal Server Error | Błąd połączenia DB | "Service temporarily unavailable" | ERROR | Tak |
| 500 | Internal Server Error | Błąd Doctrine | "Error processing request" | ERROR | Tak |
| 500 | Internal Server Error | Nieoczekiwany błąd | "Unexpected error occurred" | CRITICAL | Tak |

## 8. Rozważania dotyczące wydajności

### 8.1 Database Performance

**Charakterystyka query**:
- Simple SELECT na małej tabeli (6 rekordów)
- ORDER BY na indexed column (może nie być konieczne)
- Brak JOINów
- Brak paginacji (niewielka ilość danych)

**Optymalizacje**:
- ✅ Indeks na `name_pl` dla sortowania (nice to have)
- ✅ Query cache na poziomie Doctrine
- ❌ Pagination nie jest potrzebna (tylko 6 rekordów)
- ❌ Lazy loading relations nie jest potrzebne (nie pobieramy exercises)

**Expected query time**: < 5ms

### 8.2 HTTP Caching

**Rekomendacja**: Implementacja HTTP cache headers

**Headers**:
```
Cache-Control: public, max-age=3600
ETag: "33a64df551425fcc55e4d42a148795d9f25f89d4"
Last-Modified: Thu, 10 Oct 2024 18:11:37 GMT
```

**Implementacja w kontrolerze**:
```php
public function __invoke(Request $request): Response
{
    $response = new JsonResponse();
    
    // ETag based on data version
    $etag = md5(serialize($muscleCategories));
    $response->setEtag($etag);
    $response->setPublic();
    $response->setMaxAge(3600); // 1 hour
    
    // Check if client has valid cache
    if ($response->isNotModified($request)) {
        return $response;
    }
    
    // Set data and return
    $response->setData($muscleCategoryDtos);
    return $response;
}
```

**Korzyści**:
- Redukcja obciążenia serwera
- Mniejsze zużycie bandwidth
- Szybsze odpowiedzi dla klienta
- CDN-friendly

### 8.3 Application-Level Caching

**Rekomendacja**: Symfony Cache dla wyników query

**Implementacja**:
```php
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class MuscleCategoryRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CacheInterface $cache,
    ) {
        parent::__construct($registry, MuscleCategory::class);
    }

    public function findAll(): array
    {
        return $this->cache->get('muscle_categories_all', function (ItemInterface $item) {
            $item->expiresAfter(3600); // 1 hour
            
            return $this->createQueryBuilder('mc')
                ->orderBy('mc.namePl', 'ASC')
                ->getQuery()
                ->getResult();
        });
    }
}
```

**Cache Invalidation**:
- TTL: 1 godzina (3600s)
- Manual invalidation przy dodaniu/edycji kategorii (jeśli będzie taka funkcjonalność)
- Cache key: `muscle_categories_all`
- Cache backend: Redis (preferowany) lub APCu

### 8.4 Response Size

**Szacowany rozmiar**:
- Pojedynczy obiekt: ~150 bytes
- 6 obiektów: ~900 bytes
- Z JSON overhead: ~1.2 KB

**Optymalizacje**:
- ✅ GZIP compression (automatyczne przez nginx/apache)
- ❌ Field filtering nie jest potrzebne (mała payload)
- ❌ Binary format (Protobuf) - overkill dla tego case'u

**Kompresja GZIP**: ~400 bytes (66% redukcja)

### 8.5 Concurrency

**Expected load**:
- Frontend zazwyczaj pobiera to raz przy starcie aplikacji
- Low concurrency (< 10 simultaneous requests)
- Cache znacznie redukuje database hits

**Bottlenecks**: Brak (dla spodziewanego ruchu)

### 8.6 Monitoring Metrics

**Key Performance Indicators**:
- Response time (p50, p95, p99)
- Cache hit rate
- Database query time
- Error rate
- Throughput (requests/second)

**Thresholds**:
- Response time p95: < 50ms (bez cache), < 5ms (z cache)
- Cache hit rate: > 95%
- Error rate: < 0.1%

**Narzędzia**:
- Symfony Profiler (dev)
- Prometheus + Grafana (production)
- Application Performance Monitoring (APM) - opcjonalnie

### 8.7 Scalability

**Horizontal Scaling**:
- ✅ Stateless endpoint - łatwo skalować
- ✅ Cache w Redis - współdzielony między instancjami
- ✅ Database read replicas - możliwe jeśli potrzebne

**Vertical Scaling**: Nie jest potrzebne dla tego endpointu

### 8.8 Performance Budget

| Metric | Target | Acceptable | Critical |
|--------|--------|------------|----------|
| Response Time (p95) | < 50ms | < 100ms | > 200ms |
| Response Time with Cache | < 5ms | < 10ms | > 20ms |
| Database Query Time | < 5ms | < 10ms | > 50ms |
| Payload Size | < 2KB | < 5KB | > 10KB |
| Cache Hit Rate | > 95% | > 90% | < 80% |

## 9. Testy funkcjonalne

### 9.1 Przegląd testów

Testy funkcjonalne dla tego endpointa powinny weryfikować:
- ✅ Poprawny status code (200 OK)
- ✅ Poprawny format odpowiedzi (JSON)
- ✅ Obecność wszystkich wymaganych pól w DTO
- ✅ Poprawne typy danych
- ✅ Sortowanie wyników po `namePl`
- ✅ Obsługa nieprawidłowych metod HTTP (405)
- ✅ Obsługa CORS headers
- ✅ Obsługa pustej bazy danych

### 9.2 Struktura pliku testowego

**Plik**: `tests/Functional/Controller/MuscleCategory/GetMuscleCategoriesControllerTest.php`

**Lokalizacja**: Mirror struktura kontrolera w katalogu `tests/`

### 9.3 Pełny kod testu funkcjonalnego

```php
<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\MuscleCategory;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GetMuscleCategoriesControllerTest extends WebTestCase
{
    /**
     * Test podstawowy - endpoint zwraca 200 OK
     */
    public function testGetMuscleCategoriesReturns200(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'GET',
            uri: '/api/v1/muscle-categories',
            server: ['CONTENT_TYPE' => 'application/json']
        );
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
    
    /**
     * Test - każdy element zawiera wszystkie wymagane pola
     */
    public function testGetMuscleCategoriesReturnsAllRequiredFields(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'GET',
            uri: '/api/v1/muscle-categories'
        );
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        // Sprawdź czy mamy jakieś kategorie
        $this->assertNotEmpty($data, 'Response should contain muscle categories');
        
        // Sprawdź każdą kategorię
        foreach ($data as $category) {
            $this->assertArrayHasKey('id', $category);
            $this->assertArrayHasKey('namePl', $category);
            $this->assertArrayHasKey('nameEn', $category);
            $this->assertArrayHasKey('createdAt', $category);
            
            // Sprawdź typy danych
            $this->assertIsString($category['id']);
            $this->assertIsString($category['namePl']);
            $this->assertIsString($category['nameEn']);
            $this->assertIsString($category['createdAt']);
            
            // Sprawdź czy ID jest ULID (26 znaków)
            $this->assertEquals(26, strlen($category['id']));
            
            // Sprawdź czy nazwy nie są puste
            $this->assertNotEmpty($category['namePl']);
            $this->assertNotEmpty($category['nameEn']);
            
            // Sprawdź format daty
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
                $category['createdAt'],
                'createdAt should be in ISO 8601 format'
            );
        }
    }
    
    /**
     * Test - odpowiedź zawiera oczekiwaną liczbę kategorii (6)
     */
    public function testGetMuscleCategoriesReturnsExpectedCount(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'GET',
            uri: '/api/v1/muscle-categories'
        );
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        // W standardowej konfiguracji powinno być 6 kategorii
        $this->assertCount(6, $data, 'Should return 6 muscle categories');
    }
    
    /**
     * Test - nieprawidłowa metoda HTTP zwraca 405
     */
    public function testGetMuscleCategoriesWithInvalidMethodReturns405(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'POST',
            uri: '/api/v1/muscle-categories'
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }
    
    /**
     * Test - metoda PUT zwraca 405
     */
    public function testGetMuscleCategoriesWithPutMethodReturns405(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'PUT',
            uri: '/api/v1/muscle-categories'
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }
    
    /**
     * Test - metoda DELETE zwraca 405
     */
    public function testGetMuscleCategoriesWithDeleteMethodReturns405(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'DELETE',
            uri: '/api/v1/muscle-categories'
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }
    
    /**
     * Test - endpoint nie wymaga autoryzacji (publiczny)
     */
    public function testGetMuscleCategoriesDoesNotRequireAuthentication(): void
    {
        $client = static::createClient();
        
        // Request bez tokena autoryzacji
        $client->request(
            method: 'GET',
            uri: '/api/v1/muscle-categories'
        );
        
        // Powinno zwrócić 200, nie 401
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
```

### 9.4 Setup testów - Fixtures

Aby testy działały poprawnie, należy upewnić się, że baza testowa zawiera dane kategorii mięśni.

**Opcja 1: Doctrine Fixtures (zalecane)**

```php
// src/DataFixtures/MuscleCategoryFixtures.php
<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Entity\MuscleCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class MuscleCategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            ['Klatka piersiowa', 'Chest'],
            ['Plecy', 'Back'],
            ['Nogi', 'Legs'],
            ['Barki', 'Shoulders'],
            ['Biceps', 'Biceps'],
            ['Triceps', 'Triceps'],
        ];

        foreach ($categories as [$namePl, $nameEn]) {
            $category = MuscleCategory::create($namePl, $nameEn);
            $manager->persist($category);
        }

        $manager->flush();
    }
}
```

**Opcja 2: Migration z danymi**

Dane mogą być załadowane przez migrację (już istniejącą w projekcie).

**Opcja 3: Setup w metodzie setUp()**

```php
protected function setUp(): void
{
    parent::setUp();
    
    // Załaduj fixtures przed każdym testem
    static::bootKernel();
    $container = static::getContainer();
    
    // Wyczyść i załaduj fixtures
    $connection = $container->get('doctrine')->getConnection();
    $connection->executeStatement('TRUNCATE muscle_categories CASCADE');
    
    // Załaduj fixtures
    // ... kod ładowania danych testowych
}
```

### 9.5 Konfiguracja środowiska testowego

**Plik**: `phpunit.xml.dist` (powinien już istnieć)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true">
    <testsuites>
        <testsuite name="Functional">
            <directory>tests/Functional</directory>
        </testsuite>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>

    <php>
        <ini name="display_errors" value="1" />
        <ini name="error_reporting" value="-1" />
        <server name="APP_ENV" value="test" force="true" />
        <server name="KERNEL_CLASS" value="App\Kernel" />
        <server name="SYMFONY_DEPRECATIONS_HELPER" value="disabled" />
    </php>
</phpunit>
```

**Plik**: `.env.test` (środowisko testowe)

```env
DATABASE_URL="postgresql://test_user:test_pass@postgres:5432/workout_tracker_test?serverVersion=16&charset=utf8"
APP_ENV=test
APP_DEBUG=1
```

### 9.6 Uruchamianie testów

**Wszystkie testy funkcjonalne**:
```bash
docker exec workouttracker-php-1 php bin/phpunit tests/Functional/
```

**Tylko testy dla muscle categories**:
```bash
docker exec workouttracker-php-1 php bin/phpunit tests/Functional/Controller/MuscleCategory/
```

**Konkretny test**:
```bash
docker exec workouttracker-php-1 php bin/phpunit --filter testGetMuscleCategoriesReturns200
```

**Z coverage** (wymaga Xdebug):
```bash
docker exec workouttracker-php-1 php bin/phpunit --coverage-html var/coverage tests/Functional/Controller/MuscleCategory/
```

**Z verbose output**:
```bash
docker exec workouttracker-php-1 php bin/phpunit --verbose tests/Functional/Controller/MuscleCategory/
```

### 9.7 Oczekiwane wyniki testów

Po uruchomieniu testów powinniśmy zobaczyć:

```
PHPUnit 11.x.x by Sebastian Bergmann and contributors.

Runtime:       PHP 8.4.x
Configuration: phpunit.xml.dist

.............                                                     13 / 13 (100%)

Time: 00:01.234, Memory: 24.00 MB

OK (13 tests, 45 assertions)
```

### 9.8 Coverage expectations

**Oczekiwany coverage dla nowego kodu**:
- Controller: 100% (wszystkie linie pokryte testami funkcjonalnymi)
- DTO: 100% (pokryte przez testy funkcjonalne)
- Repository: 80%+ (część przez testy jednostkowe, część przez funkcjonalne)


### 9.10 Checklist przed merge

- [ ] Wszystkie testy przechodzą
- [ ] Coverage ≥ 80% dla nowego kodu
- [ ] Testy są deterministyczne (nie zależą od czasu/random danych)
- [ ] Testy działają w izolacji (jeden test nie wpływa na drugi)
- [ ] Fixtures/dane testowe są poprawnie załadowane
- [ ] Testy sprawdzają wszystkie wymagane scenariusze
- [ ] Testy sprawdzają edge cases
- [ ] Testy mają czytelne nazwy i komentarze
- [ ] Assertions mają custom messages dla lepszej czytelności błędów
- [ ] Testy działają w środowisku CI/CD

## 10. Kroki implementacji

### Krok 1: Utworzenie Repository Interface

**Plik**: `src/Domain/Repository/MuscleCategoryRepositoryInterface.php`

**Akcje**:
1. Utworzyć interface w namespace `App\Domain\Repository`
2. Zdefiniować metodę `findAll(): array`
3. Dodać PHPDoc z typem zwracanym `@return array<int, MuscleCategory>`

**Weryfikacja**:
- PHPStan level 9 passes
- Interface znajduje się w Domain layer

**Czas**: 5 min

### Krok 2: Implementacja Repository

**Plik**: `src/Infrastructure/Repository/MuscleCategoryRepository.php`

**Akcje**:
1. Utworzyć klasę rozszerzającą `ServiceEntityRepository`
2. Implementować `MuscleCategoryRepositoryInterface`
3. Dodać konstruktor z `ManagerRegistry`
4. Zaimplementować `findAll()` z ORDER BY `namePl`
5. Dodać PHPDoc annotations

**Weryfikacja**:
- PHPStan level 9 passes
- Repository automatycznie zarejestrowane przez autowiring

**Czas**: 10 min

### Krok 3: Utworzenie Output DTO

**Plik**: `src/Infrastructure/Api/Output/MuscleCategoryDto.php`

**Akcje**:
1. Utworzyć readonly class z publicznymi properties
2. Dodać konstruktor z property promotion
3. Zaimplementować metodę `fromEntity(MuscleCategory $category): self`
4. Użyć named arguments w konstruktorze

**Weryfikacja**:
- PHPStan level 9 passes
- DTO jest readonly i immutable
- Correct type hints

**Czas**: 10 min

### Krok 4: Implementacja Controller

**Plik**: `src/Infrastructure/Controller/MuscleCategory/GetMuscleCategoriesController.php`

**Akcje**:
1. Utworzyć final class rozszerzającą `AbstractController`
2. Dodać #[Route] attribute z path, name, methods
3. Dodać konstruktor z dependency injection repository
4. Zaimplementować `__invoke()` method
5. Pobrać kategorie z repository
6. Zmapować entities do DTOs używając `array_map`
7. Zwrócić JsonResponse z kodem 200

**Weryfikacja**:
- Route jest dostępny: `php bin/console debug:router | grep muscle-categories`
- PHPStan level 9 passes
- Controller jest thin (tylko routing logic)

**Czas**: 15 min

**Komenda weryfikująca route**:
```bash
docker exec workouttracker-php-1 php bin/console debug:router get_muscle_categories
```

### Krok 5: Dodanie Error Handling

**Plik**: Modyfikacja `GetMuscleCategoriesController.php`

**Akcje**:
1. Dodać try-catch blok dla database errors
2. Inject `LoggerInterface` w konstruktorze
3. Logować błędy z odpowiednim poziomem
4. Zwracać generyczne komunikaty błędów

**Weryfikacja**:
- Symulować błąd DB i sprawdzić logi
- Sprawdzić czy stack trace nie jest wyświetlany w response

**Czas**: 10 min

### Krok 6: Dodanie testów jednostkowych Repository

**Plik**: `tests/Unit/Infrastructure/Repository/MuscleCategoryRepositoryTest.php`

**Akcje**:
1. Utworzyć test class rozszerzający `TestCase`
2. Mock `ManagerRegistry`
3. Test `findAll()` zwraca tablicę
4. Test sortowanie po `namePl`

**Weryfikacja**:
```bash
docker exec workouttracker-php-1 php bin/phpunit tests/Unit/Infrastructure/Repository/MuscleCategoryRepositoryTest.php
```

**Czas**: 15 min

### Krok 7: Dodanie testów funkcjonalnych Controller

**Plik**: `tests/Functional/Controller/MuscleCategory/GetMuscleCategoriesControllerTest.php`

**Akcje**:
1. Utworzyć test class rozszerzający `WebTestCase`
2. Zaimplementować 13+ test cases (patrz sekcja 9 - Testy funkcjonalne)
3. Setup fixtures/dane testowe (6 kategorii mięśni)
4. Sprawdzić wszystkie scenariusze: sukces, błędy, edge cases
5. Dodać testy dla CORS, unikalności, sortowania

**Pełny kod i szczegóły**: Zobacz sekcja 9 - Testy funkcjonalne

**Test Cases** (podstawowe):
- `testGetMuscleCategoriesReturns200()` - status 200
- `testGetMuscleCategoriesReturnsValidJson()` - valid JSON
- `testGetMuscleCategoriesReturnsAllRequiredFields()` - pola w DTO
- `testGetMuscleCategoriesIsSortedByNamePl()` - sortowanie
- `testGetMuscleCategoriesWithInvalidMethodReturns405()` - błędna metoda
- `testGetMuscleCategoriesDoesNotRequireAuthentication()` - publiczny endpoint
- `testGetMuscleCategoriesReturnsUniqueIds()` - unikalne ID
- `testGetMuscleCategoriesReturnsExpectedCount()` - 6 kategorii
- ... i więcej (patrz sekcja 9)

**Weryfikacja**:
```bash
docker exec workouttracker-php-1 php bin/phpunit tests/Functional/Controller/MuscleCategory/
```

**Czas**: 30 min (zwiększony z powodu większej liczby testów)

### Krok 8: Konfiguracja CORS

**Plik**: `config/packages/nelmio_cors.yaml`

**Akcje**:
1. Dodać konfigurację dla `/api/v1/muscle-categories`
2. Ustawić allowed origins na `%env(CORS_ALLOW_ORIGIN)%`
3. Ustawić allowed methods: GET
4. Ustawić allowed headers: Content-Type, Accept
5. Ustawić max_age: 3600

**Weryfikacja**:
- Sprawdzić CORS headers w response
- Test z frontendem Next.js

**Czas**: 5 min

### Krok 9: Dodanie HTTP Caching (opcjonalne)

**Plik**: Modyfikacja `GetMuscleCategoriesController.php`

**Akcje**:
1. Użyć `Response` zamiast `JsonResponse`
2. Dodać `setETag()` based on data hash
3. Dodać `setPublic()` i `setMaxAge(3600)`
4. Dodać `isNotModified()` check

**Weryfikacja**:
- Sprawdzić headers: `Cache-Control`, `ETag`
- Drugi request z tym samym ETag zwraca 304 Not Modified

**Czas**: 15 min

### Krok 10: Dodanie Application Cache (opcjonalne)

**Plik**: Modyfikacja `MuscleCategoryRepository.php`

**Akcje**:
1. Inject `CacheInterface` w konstruktorze
2. Wrap query w `$cache->get()`
3. Ustawić TTL: 3600s
4. Cache key: `muscle_categories_all`

**Weryfikacja**:
- Sprawdzić Redis/cache backend
- Verify cache hit w subsequent requests

**Czas**: 10 min

### Krok 11: Dodanie Rate Limiting (opcjonalne, zalecane)

**Plik**: `config/packages/rate_limiter.yaml`

**Akcje**:
1. Skonfigurować rate limiter `muscle_categories_api`
2. Policy: sliding_window, limit: 60, interval: 60s
3. Dodać `#[RateLimit]` attribute w kontrollerze

**Weryfikacja**:
- Test z 61 requestami w ciągu minuty
- Sprawdzić 429 Too Many Requests response

**Czas**: 10 min

### Krok 12: Aktualizacja dokumentacji API

**Plik**: `docs/swagger.json`

**Akcje**:
1. Sprawdzić czy endpoint `/api/v1/muscle-categories` jest już udokumentowany
2. Jeśli nie - dodać definicję endpointa
3. Dodać przykłady response
4. Dodać opis error codes

**Weryfikacja**:
- Swagger UI wyświetla endpoint
- Dokumentacja jest kompletna

**Czas**: 10 min

### Krok 13: Uruchomienie lintera PHP CS Fixer

**Komenda**:
```bash
docker exec workouttracker-php-1 vendor/bin/php-cs-fixer fix src/
```

**Weryfikacja**:
- Brak zmian lub tylko formatting fixes
- Kod zgodny z PSR-12

**Czas**: 2 min

### Krok 14: Uruchomienie PHPStan

**Komenda**:
```bash
docker exec workouttracker-php-1 vendor/bin/phpstan analyse src tests --level=9
```

**Weryfikacja**:
- Brak błędów
- Level 9 passes

**Czas**: 2 min

### Krok 15: Uruchomienie wszystkich testów

**Komenda**:
```bash
docker exec workouttracker-php-1 php bin/phpunit
```

**Weryfikacja**:
- Wszystkie testy przechodzą
- Code coverage > 80% dla nowego kodu

**Czas**: 5 min

### Krok 16: Manualne testy API

**Akcje**:
1. Uruchomić aplikację: `docker compose up -d`
2. Test GET request:
```bash
curl -X GET "http://localhost:8000/api/v1/muscle-categories" \
  -H "Accept: application/json"
```
3. Sprawdzić response status: 200
4. Sprawdzić response format
5. Sprawdzić cache headers
6. Test z frontendem Next.js

**Weryfikacja**:
- ✅ Status 200 OK
- ✅ Valid JSON response
- ✅ All fields present
- ✅ Data sorted correctly
- ✅ CORS headers present
- ✅ Cache headers present (jeśli zaimplementowane)
- ✅ Frontend może pobrać dane

**Czas**: 10 min

### Krok 17: Code Review Checklist

**Do sprawdzenia**:
- [ ] Kod zgodny z Symfony best practices
- [ ] PHP 8.4 features używane (readonly, typed properties)
- [ ] Hexagonal architecture zachowana
- [ ] Controller jest thin
- [ ] Repository używa interface
- [ ] DTO jest immutable
- [ ] Proper error handling
- [ ] Proper logging
- [ ] Security considerations addressed
- [ ] Tests cover happy path i edge cases
- [ ] Documentation updated
- [ ] PHPStan level 9 passes
- [ ] PHP CS Fixer passes
- [ ] No code duplication
- [ ] Performance considerations addressed

**Czas**: 15 min

---

## Podsumowanie czasowe

| Krok | Opis | Czas (min) |
|------|------|------------|
| 1 | Repository Interface | 5 |
| 2 | Repository Implementation | 10 |
| 3 | Output DTO | 10 |
| 4 | Controller | 15 |
| 5 | Error Handling | 10 |
| 6 | Unit Tests | 15 |
| 7 | Functional Tests (13+ test cases) | 30 |
| 8 | CORS Configuration | 5 |
| 9 | HTTP Caching | 15 |
| 10 | Application Cache | 10 |
| 11 | Rate Limiting | 10 |
| 12 | API Documentation | 10 |
| 13 | PHP CS Fixer | 2 |
| 14 | PHPStan | 2 |
| 15 | Tests | 5 |
| 16 | Manual Testing | 10 |
| 17 | Code Review | 15 |
| **TOTAL** | | **179 min (~3h)** |

**Czas minimalny (bez opcjonalnych optimalizacji)**: ~110 min (~2h)
**Czas pełny (z wszystkimi optimalizacjami)**: ~180 min (~3h)

---

## Notatki końcowe

### Priorytety implementacji

**MUST HAVE** (Core functionality):
1. Repository Interface & Implementation
2. Output DTO
3. Controller
4. Basic Error Handling
5. Functional Tests
6. CORS Configuration

**SHOULD HAVE** (Quality):
1. Unit Tests
2. Comprehensive Error Handling & Logging
3. API Documentation Update
4. PHPStan & PHP CS Fixer

**NICE TO HAVE** (Performance):
1. HTTP Caching
2. Application-level Cache
3. Rate Limiting

### Potencjalne rozszerzenia w przyszłości

1. **Internationalization (i18n)**:
   - Query parameter `?lang=en` lub `?lang=pl`
   - Zwracać tylko odpowiednią nazwę w wybranym języku

2. **Filtering**:
   - `?hasExercises=true` - tylko kategorie z ćwiczeniami

3. **Statistics**:
   - Dodać pole `exercisesCount` w DTO
   - Pokazać ile ćwiczeń jest w każdej kategorii

4. **Admin endpoints**:
   - POST /api/v1/muscle-categories - dodawanie kategorii
   - PUT /api/v1/muscle-categories/{id} - edycja
   - DELETE /api/v1/muscle-categories/{id} - usuwanie

5. **GraphQL alternative**:
   - GraphQL endpoint dla większej elastyczności

### Dependency Graph

```
GetMuscleCategoriesController
    ↓ depends on
MuscleCategoryRepositoryInterface
    ↓ implemented by
MuscleCategoryRepository
    ↓ uses
MuscleCategory (Entity)

GetMuscleCategoriesController
    ↓ uses
MuscleCategoryDto
    ↓ transforms
MuscleCategory (Entity)
```

### Kluczowe decyzje architektoniczne

1. **Brak Service Layer**: Ze względu na prostotę endpointa (tylko SELECT), service layer nie jest konieczny. Repository jest wystarczający.

2. **Public Endpoint**: Dane o kategoriach mięśni nie są wrażliwe, dlatego endpoint jest publiczny bez autoryzacji.

3. **No Pagination**: Tylko 6 kategorii - pagination jest niepotrzebna.

4. **Eager Loading**: Nie pobieramy relacji `exercises` - nie są potrzebne w tym endpoincie.

5. **DTO Pattern**: Używamy DTO zamiast bezpośredniej serializacji entity, aby oddzielić API contract od modelu domenowego.

6. **Sortowanie po nazwie polskiej**: Domyślnie sortujemy po `namePl`, ponieważ aplikacja jest głównie dla polskich użytkowników.

---

**Status**: Gotowy do implementacji
**Ostatnia aktualizacja**: 2024-10-11
**Wersja**: 1.0

