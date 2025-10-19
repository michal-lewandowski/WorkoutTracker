# API Endpoint Implementation Plan: Get Current User Profile

## 1. Przegląd punktu końcowego

Endpoint `GET /api/v1/auth/me` umożliwia pobranie profilu obecnie zalogowanego użytkownika. Jest to chroniony endpoint wymagający prawidłowego tokena JWT w nagłówku Authorization. Zwraca podstawowe informacje o użytkowniku: identyfikator (uuid4), adres email oraz datę utworzenia konta.

**Główne cechy**:
- Prosta operacja odczytu (read-only)
- Brak parametrów wejściowych (tożsamość użytkownika wyodrębniona z tokena JWT)
- Zwraca publiczne dane profilu użytkownika
- Nie modyfikuje stanu systemu

## 2. Szczegóły żądania

### Metoda HTTP
```
GET /api/v1/auth/me
```

### Struktura URL
```
/api/v1/auth/me
```

### Nagłówki (Headers)
**Wymagane**:
- `Authorization: Bearer <JWT_TOKEN>` - Token JWT uzyskany z endpointu `/auth/login` lub `/auth/register`
- `Accept: application/json`

**Opcjonalne**:
- `Content-Type: application/json` (nie jest wymagany dla GET, ale zalecany dla spójności API)

### Parametry

**Parametry URL (Path Parameters)**: Brak

**Parametry zapytania (Query Parameters)**: Brak

**Parametry wymagane**: Brak - tożsamość użytkownika jest automatycznie wyodrębniana z tokena JWT przez system bezpieczeństwa Symfony.

**Parametry opcjonalne**: Brak

### Request Body
Brak - endpoint GET nie przyjmuje body.

### Przykład żądania
```bash
curl -X GET https://localhost/api/v1/auth/me \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -H "Accept: application/json"
```

## 3. Wykorzystywane typy

### Input DTOs
**Brak** - endpoint nie przyjmuje żadnych danych wejściowych. Użytkownik jest identyfikowany na podstawie tokena JWT.

### Output DTOs

#### UserDto (istniejący)
**Lokalizacja**: `src/Infrastructure/Api/Output/UserDto.php`

```php
final readonly class UserDto
{
    public function __construct(
        public string $id,                      // uuid4 użytkownika
        public string $email,                   // Adres email
        public \DateTimeImmutable $createdAt,   // Data utworzenia konta
    ) {}
    
    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->getId(),
            email: $user->getEmail(),
            createdAt: $user->getCreatedAt(),
        );
    }
}
```

**Uwagi**:
- Klasa jest readonly i immutable
- Zawiera statyczną metodę factory `fromEntity()` do konwersji z encji User
- Nie zawiera wrażliwych danych (np. hasła)
- Format createdAt: ISO 8601 (automatycznie serializowany przez Symfony Serializer)

### Command Models
**Brak** - operacja odczytu nie wymaga modeli Command (Command pattern stosowany dla operacji modyfikujących stan).

### Encje domenowe

#### User (istniejąca)
**Lokalizacja**: `src/Domain/Entity/User.php`

Wykorzystywane pola:
- `id` (string, uuid4)
- `email` (string)
- `createdAt` (DateTimeImmutable)

**Uwaga**: Encja User implementuje `UserInterface` i `PasswordAuthenticatedUserInterface`, co umożliwia integrację z systemem bezpieczeństwa Symfony.

## 4. Szczegóły odpowiedzi

### Odpowiedź sukcesu (200 OK)

**Status Code**: `200 OK`

**Content-Type**: `application/json`

**Body Structure**:
```json
{
  "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
  "email": "user@example.com",
  "createdAt": "2025-10-11T10:30:00+00:00"
}
```

**Przykład rzeczywistej odpowiedzi**:
```json
{
  "id": "01JABA5X7G9VQW3N8C2T4H6MYR",
  "email": "john.doe@example.com",
  "createdAt": "2025-10-10T14:23:45+00:00"
}
```

### Odpowiedzi błędów

#### 401 Unauthorized - Brak tokena
**Scenariusz**: Żądanie wysłane bez nagłówka Authorization

**Body**:
```json
{
  "code": 401,
  "message": "JWT Token not found"
}
```

#### 401 Unauthorized - Nieprawidłowy token
**Scenariusz**: Token JWT jest nieprawidłowy (zła sygnatura, zmodyfikowany)

**Body**:
```json
{
  "code": 401,
  "message": "Invalid JWT Token"
}
```

#### 401 Unauthorized - Wygasły token
**Scenariusz**: Token JWT jest wygasły (expired)

**Body**:
```json
{
  "code": 401,
  "message": "Expired JWT Token"
}
```

#### 500 Internal Server Error
**Scenariusz**: Nieoczekiwany błąd serwera (np. problem z bazą danych)

**Body**:
```json
{
  "code": 500,
  "message": "An error occurred"
}
```

**Uwaga**: W środowisku produkcyjnym szczegóły błędów serwera nie powinny być ujawniane klientowi.

## 5. Przepływ danych

### Diagram przepływu danych

```
1. Klient (Frontend)
   ↓ [GET /api/v1/auth/me + JWT Token]
   
2. Nginx/Web Server
   ↓ [Przekazanie żądania do Symfony]
   
3. Symfony Firewall (Security Layer)
   ↓ [Wyodrębnienie tokena z nagłówka Authorization]
   ↓ [Walidacja tokena przez Lexik JWT Bundle]
   ↓ [Weryfikacja sygnatury, expiration, format]
   ↓ [Pobranie email z payload tokena]
   
4. User Provider (security.yaml)
   ↓ [Załadowanie użytkownika z bazy danych po email]
   ↓ [SELECT * FROM users WHERE email = ?]
   
5. Security Context
   ↓ [Zapisanie obiektu User w kontekście bezpieczeństwa]
   
6. Controller: GetCurrentUserController
   ↓ [Pobranie User z $this->getUser()]
   
7. Mapper: UserDto::fromEntity()
   ↓ [Konwersja User entity → UserDto]
   
8. Controller Response
   ↓ [return $this->json($userDto)]
   
9. Symfony Serializer
   ↓ [Serializacja UserDto do JSON]
   
10. Response → Klient
   [200 OK + JSON body]
```

### Szczegółowy opis kroków

**Krok 1-2: Przyjęcie żądania**
- Klient wysyła żądanie GET z tokenem JWT w nagłówku Authorization
- Web server (Nginx) przekazuje żądanie do aplikacji Symfony

**Krok 3: Walidacja JWT przez Firewall**
- Firewall `api` (pattern: `^/api`) przechwytuje żądanie
- Lexik JWT Bundle ekstraktuje token z nagłówka `Authorization: Bearer <token>`
- Weryfikacja:
  - Sygnatura tokena (RS256/HS256)
  - Data wygaśnięcia (exp claim)
  - Format tokena
  - Obecność wymaganych claims (username/email)
- **Błąd 401**: Jeśli którykolwiek z powyższych kroków zawiedzie

**Krok 4: Załadowanie użytkownika**
- User Provider (`app_user_provider`) pobiera email z tokena JWT (claim 'username')
- Wykonuje zapytanie do bazy danych: `SELECT * FROM users WHERE email = ?`
- Tworzy obiekt User entity
- **Błąd 401**: Jeśli użytkownik nie istnieje w bazie danych

**Krok 5: Security Context**
- Symfony zapisuje załadowanego użytkownika w Security Context
- Użytkownik jest dostępny w kontrolerze przez `$this->getUser()`

**Krok 6: Wykonanie kontrolera**
- Controller `GetCurrentUserController` jest wywoływany
- Metoda `$this->getUser()` zwraca autentykowanego użytkownika typu User
- Nie są potrzebne dodatkowe zapytania do bazy danych

**Krok 7: Mapowanie danych**
- Wywołanie `UserDto::fromEntity($user)`
- Konwersja encji na DTO z publicznymi danymi (bez hasła)
- DTO zawiera tylko: id, email, createdAt

**Krok 8-9: Serializacja i odpowiedź**
- `$this->json($userDto)` automatycznie serializuje DTO do JSON
- Symfony Serializer konwertuje DateTimeImmutable do formatu ISO 8601
- Ustawienie nagłówka `Content-Type: application/json`

**Krok 10: Zwrot odpowiedzi**
- Status 200 OK
- JSON body z danymi użytkownika

### Interakcje z bazą danych

**Zapytania wykonywane**:
1. **SELECT podczas walidacji tokena** (przez User Provider):
```sql
SELECT * FROM users WHERE email = :email LIMIT 1;
```

**Liczba zapytań**: 1 query (wykonywane automatycznie przez Security Layer)

**Performance**: Bardzo szybkie dzięki indeksowi na kolumnie `email` (zdefiniowanym w User entity).

### Cache
- Brak cache'owania (dane użytkownika mogą się zmieniać)
- Każde żądanie pobiera świeże dane z bazy
- Opcjonalnie można dodać cache na poziomie User Provider dla optymalizacji

## 6. Względy bezpieczeństwa

### 6.1 Uwierzytelnianie (Authentication)

#### JWT Token
**Mechanizm**: Lexik JWT Authentication Bundle
- Typ: Bearer token w nagłówku Authorization
- Format: `Authorization: Bearer <token>`
- Algorytm: RS256 (RSA with SHA-256) lub HS256
- Payload zawiera: email użytkownika, role, iat (issued at), exp (expiration)

**Walidacja tokena**:
- Weryfikacja sygnatury za pomocą klucza publicznego (RS256) lub tajnego klucza (HS256)
- Sprawdzenie daty wygaśnięcia (exp claim)
- Weryfikacja issuer i audience (jeśli skonfigurowane)

**Konfiguracja** (w `config/packages/lexik_jwt_authentication.yaml`):
```yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600  # 1 godzina
```

#### Firewall Configuration
**Plik**: `config/packages/security.yaml`

```yaml
firewalls:
    api:
        pattern: ^/api
        stateless: true
        jwt: ~

access_control:
    - { path: ^/api/v1/auth/login, roles: PUBLIC_ACCESS }
    - { path: ^/api/v1/auth/register, roles: PUBLIC_ACCESS }
    - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

**Wyjaśnienie**:
- Firewall `api` chroni wszystkie endpointy pod `/api`
- `stateless: true` - brak sesji, każde żądanie musi zawierać token
- `jwt: ~` - aktywacja walidacji JWT
- Access control wymaga `IS_AUTHENTICATED_FULLY` dla `/api` (z wyjątkiem login/register)

### 6.2 Autoryzacja (Authorization)

#### Zasady dostępu
- **User może zobaczyć tylko swój profil**: Zapewnione przez mechanizm autentykacji - użytkownik może pobrać tylko dane zalogowanego konta (z tokena)
- **Brak możliwości podszywania się**: Nie ma parametru `userId` w URL - użytkownik jest identyfikowany wyłącznie przez token JWT
- **Role nie są wymagane**: Wystarczy `IS_AUTHENTICATED_FULLY`, każdy zalogowany użytkownik może zobaczyć swój profil

#### Brak security voters
Nie są potrzebne custom Security Voters, ponieważ:
- Użytkownik może zobaczyć tylko własny profil (wynikający z tokena)
- Brak złożonej logiki autoryzacji
- Firewall i access control wystarczają

### 6.3 Zabezpieczenie danych

#### Dane nie ujawniane w odpowiedzi
- `passwordHash` - **NIE** jest zwracane (nie ma w UserDto)
- `updatedAt` - nie jest istotne dla klienta (opcjonalnie można dodać)
- `workoutSessions` - relacje nie są ładowane (LAZY loading)

#### Tylko dane publiczne
UserDto zawiera wyłącznie:
- `id` - identyfikator użytkownika (uuid4, publiczny)
- `email` - adres email (publiczny dla właściciela)
- `createdAt` - data rejestracji (publiczny)

### 6.4 Ochrona przed atakami

#### CORS (Cross-Origin Resource Sharing)
**Bundle**: nelmio/cors-bundle (zainstalowany w projekcie)

Powinien być skonfigurowany w `config/packages/nelmio_cors.yaml`:
```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['^https?://localhost(:[0-9]+)?$']
        allow_methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['Link']
        max_age: 3600
    paths:
        '^/api': ~
```

#### Rate Limiting
**Rekomendacja**: Dodanie rate limitingu dla endpointu `/auth/me`:
- Maksymalnie 60 żądań na minutę na użytkownika (IP + token)
- Ochrona przed nadużyciami i atakami DDoS

**Implementacja** (opcjonalna, zalecana dla produkcji):
```yaml
# config/packages/rate_limiter.yaml
framework:
    rate_limiter:
        api_auth:
            policy: 'sliding_window'
            limit: 60
            interval: '1 minute'
```

#### Logi bezpieczeństwa
**Co logować**:
- ✅ Nieudane próby autentykacji (nieprawidłowy/wygasły token)
- ✅ Źródło żądania (IP address)
- ❌ **NIE logować**: Treści tokenów JWT, danych użytkowników

**Przykład logowania**:
```php
// W EventListener dla authentication failures
$this->logger->warning('Failed JWT authentication', [
    'ip' => $request->getClientIp(),
    'path' => $request->getPathInfo(),
    'reason' => 'expired_token'
]);
```

### 6.5 HTTPS
**Wymaganie**: W środowisku produkcyjnym endpoint **MUSI** być dostępny wyłącznie przez HTTPS.
- Tokens JWT przesyłane w nagłówkach są wrażliwe
- HTTP (plain text) pozwala na przechwytywanie tokenów (man-in-the-middle)

**Konfiguracja Nginx** (dla produkcji):
```nginx
server {
    listen 80;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    # ... reszta konfiguracji
}
```

## 7. Obsługa błędów

### 7.1 Katalog błędów

| Kod | Scenariusz | Przyczyna | Odpowiedź | Handler |
|-----|-----------|-----------|-----------|---------|
| **200** | Sukces | Prawidłowy token, użytkownik istnieje | UserDto JSON | Controller |
| **401** | Brak tokena | Nagłówek Authorization nie został wysłany | `{"code": 401, "message": "JWT Token not found"}` | JWT Bundle |
| **401** | Nieprawidłowy token | Token ma złą sygnaturę lub jest zmodyfikowany | `{"code": 401, "message": "Invalid JWT Token"}` | JWT Bundle |
| **401** | Wygasły token | Token przekroczył czas ważności (exp) | `{"code": 401, "message": "Expired JWT Token"}` | JWT Bundle |
| **401** | Użytkownik nie istnieje | Email z tokena nie istnieje w bazie | `{"code": 401, "message": "Invalid credentials"}` | User Provider |
| **500** | Błąd serwera | Problem z bazą danych, wyjątek | `{"code": 500, "message": "An error occurred"}` | Global Exception Listener |

### 7.2 Szczegółowa obsługa błędów

#### 401 Unauthorized - Automatyczna obsługa przez Security Layer

**Obsługiwane automatycznie przez**:
- Lexik JWT Authentication Bundle
- Symfony Security Component

**Bez dodatkowej implementacji w kontrolerze**.

**Format odpowiedzi błędu 401**:
```json
{
  "code": 401,
  "message": "Invalid JWT Token"
}
```

**Warianty message**:
- `"JWT Token not found"` - brak nagłówka Authorization
- `"Invalid JWT Token"` - zła sygnatura, nieprawidłowy format
- `"Expired JWT Token"` - token wygasł

#### 500 Internal Server Error

**Obsługiwany przez**: Global Exception Listener lub Symfony error handling

**Przykładowe scenariusze**:
- Błąd połączenia z bazą danych
- Wyjątek w kodzie PHP
- Błąd serializacji

**Response** (produkcja):
```json
{
  "code": 500,
  "message": "An error occurred"
}
```

**Response** (development):
```json
{
  "code": 500,
  "message": "SQLSTATE[HY000] [2002] Connection refused",
  "trace": "..."
}
```

**Logowanie**:
```php
$this->logger->error('Failed to fetch user profile', [
    'user_id' => $user?->getId(),
    'exception' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);
```

### 7.3 Testy scenariuszy błędów

**Test case'y do implementacji**:

```php
// tests/Functional/Controller/Auth/GetCurrentUserControllerTest.php

public function testGetCurrentUserWithoutToken(): void
{
    $client = static::createClient();
    $client->request('GET', '/api/v1/auth/me');
    
    $this->assertResponseStatusCodeSame(401);
}

public function testGetCurrentUserWithInvalidToken(): void
{
    $client = static::createClient();
    $client->request('GET', '/api/v1/auth/me', [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer invalid_token_here'
    ]);
    
    $this->assertResponseStatusCodeSame(401);
}

public function testGetCurrentUserWithExpiredToken(): void
{
    $expiredToken = $this->generateExpiredToken();
    $client = static::createClient();
    $client->request('GET', '/api/v1/auth/me', [], [], [
        'HTTP_AUTHORIZATION' => "Bearer $expiredToken"
    ]);
    
    $this->assertResponseStatusCodeSame(401);
}

public function testGetCurrentUserSuccess(): void
{
    $token = $this->generateValidToken('user@example.com');
    $client = static::createClient();
    $client->request('GET', '/api/v1/auth/me', [], [], [
        'HTTP_AUTHORIZATION' => "Bearer $token"
    ]);
    
    $this->assertResponseIsSuccessful();
    $this->assertResponseStatusCodeSame(200);
    $this->assertJson($client->getResponse()->getContent());
}
```

## 8. Rozważania dotyczące wydajności

### 8.1 Analiza wydajności

**Operacje wykonywane**:
1. Walidacja JWT (weryfikacja sygnatury) - **~1-5ms**
2. Query do bazy danych (SELECT user by email) - **~5-20ms** (z indeksem)
3. Mapowanie Entity → DTO - **<1ms**
4. Serializacja JSON - **<1ms**

**Całkowity czas**: ~10-30ms (w zależności od obciążenia bazy danych)

**Bottleneck**: Query do bazy danych podczas User Provider load.

### 8.2 Optymalizacje

#### Indeksowanie bazy danych
**Status**: ✅ Już zaimplementowane

W `User` entity:
```php
#[ORM\Index(name: 'idx_users_email_lower', columns: ['email'])]
```

**Efekt**: Query `SELECT * FROM users WHERE email = ?` używa indeksu, co znacząco przyspiesza wyszukiwanie (O(log n) zamiast O(n)).

#### Cache użytkownika (opcjonalnie)

**Strategia**: Cache User entity po załadowaniu przez User Provider

**Implementacja** (przykład):
```php
// src/Infrastructure/Security/CachedUserProvider.php
use Symfony\Contracts\Cache\CacheInterface;

final readonly class CachedUserProvider
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private CacheInterface $cache
    ) {}
    
    public function loadUserByIdentifier(string $email): ?User
    {
        return $this->cache->get(
            "user_$email",
            fn () => $this->repository->findByEmail($email),
            ttl: 300 // 5 minut
        );
    }
}
```

**Zalety**:
- Zmniejszenie obciążenia bazy danych
- Szybsza odpowiedź (dane z Redis/Memcached)

**Wady**:
- Możliwe stale dane (cache invalidation problem)
- Dodatkowa złożoność

**Rekomendacja**: Implementować tylko jeśli endpoint jest bardzo obciążony (>1000 req/min).

#### Lazy Loading relacji

**Status**: ✅ Domyślnie w Doctrine

`User::$workoutSessions` używa lazy loading:
```php
#[ORM\OneToMany(targetEntity: WorkoutSession::class, mappedBy: 'user')]
private Collection $workoutSessions;
```

**Efekt**: WorkoutSessions nie są ładowane podczas pobierania User (N+1 problem avoided).

#### JSON Serialization

**Symfony Serializer**: Automatycznie używa wydajnej serializacji.

**Optymalizacja** (jeśli potrzebna):
```yaml
# config/packages/framework.yaml
framework:
    serializer:
        enable_annotations: false  # Use attributes only
```

### 8.3 Monitoring i metryki

**Zalecane metryki do monitorowania**:
- Response time (p50, p95, p99)
- Request rate (req/s)
- Error rate (% of 401, 500)
- Database query time
- JWT validation time

**Narzędzia**:
- Blackfire.io dla profilowania PHP
- Symfony Profiler (dev environment)
- Prometheus + Grafana (produkcja)

### 8.4 Load Testing

**Scenariusz testowy**:
```bash
# Apache Benchmark
ab -n 10000 -c 100 -H "Authorization: Bearer <token>" \
   https://localhost/api/v1/auth/me

# Oczekiwane wyniki:
# Requests per second: >1000
# Time per request: <100ms (p95)
# Failed requests: 0
```

### 8.5 Skalowanie

**Dla dużego ruchu**:
1. **Horizontal scaling**: Dodanie więcej instancji aplikacji (load balancer)
2. **Database read replicas**: Odczyt z replik, zapis do mastera
3. **CDN**: Dla statycznych zasobów (nie dotyczy tego endpointu)
4. **Redis cache**: Cache User entities

**Architektura dla >10k req/s**:
```
Load Balancer (Nginx)
    ↓
[App Server 1] [App Server 2] [App Server N]
    ↓              ↓              ↓
    ↓→→→→→→→→→ Redis Cache ←←←←←←←
    ↓              ↓              ↓
[DB Read Replica 1] [DB Read Replica 2] [DB Master]
```

## 9. Etapy wdrożenia

### Krok 1: Utworzenie kontrolera
**Lokalizacja**: `src/Infrastructure/Controller/Auth/GetCurrentUserController.php`

**Implementacja**:
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Auth;

use App\Domain\Entity\User;
use App\Infrastructure\Api\Output\UserDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/auth/me', name: 'auth_me', methods: ['GET'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class GetCurrentUserController extends AbstractController
{
    #[Route('', name: '', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $userDto = UserDto::fromEntity($user);
        
        return $this->json($userDto, Response::HTTP_OK);
    }
}
```

**Kluczowe elementy**:
- `#[Route('/api/v1/auth/me', methods: ['GET'])]` - definicja endpointu
- `#[IsGranted('IS_AUTHENTICATED_FULLY')]` - wymóg autentykacji (redundantne z firewall, ale explicit)
- `$this->getUser()` - pobranie zalogowanego użytkownika z Security Context
- `UserDto::fromEntity()` - konwersja Entity → DTO
- `Response::HTTP_OK` (200) - kod statusu sukcesu

**Walidacja**: Brak potrzeby dodatkowej walidacji - user jest już załadowany i zwalidowany przez Security Layer.

### Krok 2: Weryfikacja konfiguracji bezpieczeństwa
**Plik**: `config/packages/security.yaml`

**Sprawdzenie**:
```yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            jwt: ~
    
    access_control:
        - { path: ^/api/v1/auth/login, roles: PUBLIC_ACCESS }
        - { path: ^/api/v1/auth/register, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

**✅ Status**: Konfiguracja już jest poprawna. Endpoint `/api/v1/auth/me` jest chroniony przez:
- Firewall `api` z JWT authentication
- Access control rule `^/api` wymagający `IS_AUTHENTICATED_FULLY`

**Żadne zmiany nie są wymagane**.

### Krok 3: Weryfikacja konfiguracji JWT
**Plik**: `config/packages/lexik_jwt_authentication.yaml`

**Sprawdzenie obecności**:
```yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600
```

**Sprawdzenie zmiennych środowiskowych** (`.env`):
```bash
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase_here
```

**✅ Status**: Jeśli `/auth/register` i `/auth/login` działają, konfiguracja JWT jest poprawna.

### Krok 4: Weryfikacja routing
**Plik**: `config/routes.yaml`

Symfony automatycznie wykrywa route z atrybutu `#[Route]` w kontrolerze dzięki konfiguracji:
```yaml
controllers:
    resource:
        path: ../src/Infrastructure/Controller/
        namespace: App\Infrastructure\Controller
    type: attribute
```

**Weryfikacja routing**:
```bash
php bin/console debug:router | grep auth_me
```

**Oczekiwany output**:
```
auth_me    GET    ANY    ANY    /api/v1/auth/me
```

### Krok 5: Testy funkcjonalne
**Lokalizacja**: `tests/Functional/Controller/Auth/GetCurrentUserControllerTest.php`

**Implementacja**:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Auth;

use App\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class GetCurrentUserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        
        // Clear database
        $this->entityManager->createQuery('DELETE FROM App\Domain\Entity\User')->execute();
    }

    public function testGetCurrentUserWithoutToken(): void
    {
        $this->client->request('GET', '/api/v1/auth/me');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetCurrentUserWithInvalidToken(): void
    {
        $this->client->request('GET', '/api/v1/auth/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid_token_abc123'
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetCurrentUserSuccess(): void
    {
        // Arrange: Create user and get valid JWT token
        $email = 'test@example.com';
        $password = 'SecurePass123!';
        
        $this->createUser($email, $password);
        $token = $this->loginAndGetToken($email, $password);

        // Act: Request current user profile
        $this->client->request('GET', '/api/v1/auth/me', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer $token"
        ]);

        // Assert: Response structure and data
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayHasKey('createdAt', $responseData);
        
        $this->assertSame($email, $responseData['email']);
        $this->assertMatchesRegularExpression('/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/', $responseData['id']); // uuid4 format
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $responseData['createdAt']); // ISO 8601
        
        // Assert: Password is NOT in response
        $this->assertArrayNotHasKey('password', $responseData);
        $this->assertArrayNotHasKey('passwordHash', $responseData);
    }

    public function testGetCurrentUserReturnsCorrectUser(): void
    {
        // Arrange: Create two users
        $user1Email = 'user1@example.com';
        $user2Email = 'user2@example.com';
        $password = 'SecurePass123!';
        
        $this->createUser($user1Email, $password);
        $this->createUser($user2Email, $password);
        
        $token1 = $this->loginAndGetToken($user1Email, $password);
        $token2 = $this->loginAndGetToken($user2Email, $password);

        // Act & Assert: User 1 sees only their profile
        $this->client->request('GET', '/api/v1/auth/me', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer $token1"
        ]);
        
        $response1 = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($user1Email, $response1['email']);

        // Act & Assert: User 2 sees only their profile
        $this->client->request('GET', '/api/v1/auth/me', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer $token2"
        ]);
        
        $response2 = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($user2Email, $response2['email']);
        
        // Users have different IDs
        $this->assertNotSame($response1['id'], $response2['id']);
    }

    private function createUser(string $email, string $password): User
    {
        $container = static::getContainer();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        
        $user = User::create($email);
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPasswordHash($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    private function loginAndGetToken(string $email, string $password): string
    {
        $this->client->request('POST', '/api/v1/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
        ]));

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        return $responseData['token'];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
```

**Pokryte scenariusze testowe**:
1. ✅ Żądanie bez tokena → 401
2. ✅ Żądanie z nieprawidłowym tokenem → 401
3. ✅ Żądanie z prawidłowym tokenem → 200 + dane użytkownika
4. ✅ Weryfikacja struktury odpowiedzi (id, email, createdAt)
5. ✅ Brak hasła w odpowiedzi
6. ✅ Każdy użytkownik widzi tylko swój profil

**Uruchomienie testów**:
```bash
php bin/phpunit tests/Functional/Controller/Auth/GetCurrentUserControllerTest.php
```

### Krok 6: Aktualizacja dokumentacji API (Swagger)
**Plik**: `docs/swagger.json`

**Status**: ✅ Specyfikacja już istnieje w pliku (dostarczona w zadaniu).

**Weryfikacja obecności**:
```json
"/auth/me": {
  "get": {
    "tags": ["Authentication"],
    "summary": "Get current user profile",
    "security": [{"bearerAuth": []}],
    "responses": {
      "200": { "description": "Current user profile" },
      "401": { "description": "Invalid or expired token" }
    }
  }
}
```

**Brak dodatkowych zmian wymaganych**.

### Krok 7: Testy manualne (opcjonalnie)

#### 7.1 Test z curl
```bash
# Krok 1: Rejestracja użytkownika
curl -X POST https://localhost/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"SecurePass123!","passwordConfirmation":"SecurePass123!"}'

# Response: {"token":"eyJ0eXAiOiJKV1QiLCJhbGc...","user":{...}}

# Krok 2: Pobranie profilu (skopiuj token z kroku 1)
curl -X GET https://localhost/api/v1/auth/me \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Accept: application/json"

# Expected response (200):
# {"id":"01JABA5X7G9VQW3N8C2T4H6MYR","email":"test@example.com","createdAt":"2025-10-11T10:30:00+00:00"}
```

#### 7.2 Test bez tokena
```bash
curl -X GET https://localhost/api/v1/auth/me \
  -H "Accept: application/json"

# Expected response (401):
# {"code":401,"message":"JWT Token not found"}
```

#### 7.3 Test z nieprawidłowym tokenem
```bash
curl -X GET https://localhost/api/v1/auth/me \
  -H "Authorization: Bearer invalid_token_here" \
  -H "Accept: application/json"

# Expected response (401):
# {"code":401,"message":"Invalid JWT Token"}
```

### Krok 8: Code review checklist

**Przed merge do głównej gałęzi, sprawdź**:

- [ ] **Kontroler**:
  - [ ] Używa `#[Route]` attribute z poprawną ścieżką `/api/v1/auth/me`
  - [ ] Metoda HTTP: GET
  - [ ] Atrybut `#[IsGranted('IS_AUTHENTICATED_FULLY')]`
  - [ ] Zwraca `JsonResponse` z kodem 200
  - [ ] Używa `UserDto::fromEntity()` do mapowania

- [ ] **Bezpieczeństwo**:
  - [ ] Firewall `api` jest aktywny i ma `jwt: ~`
  - [ ] Access control wymaga `IS_AUTHENTICATED_FULLY` dla `/api`
  - [ ] Token JWT jest walidowany automatycznie
  - [ ] Hasło nie jest zwracane w odpowiedzi

- [ ] **Testy**:
  - [ ] Wszystkie testy funkcjonalne przechodzą
  - [ ] Pokrycie: request bez tokena, z błędnym tokenem, z poprawnym tokenem
  - [ ] Weryfikacja struktury JSON response
  - [ ] Weryfikacja brak hasła w odpowiedzi

- [ ] **Dokumentacja**:
  - [ ] Swagger.json zawiera specyfikację endpointu
  - [ ] Przykłady request/response są aktualne

- [ ] **Code Quality**:
  - [ ] PHP CS Fixer: `vendor/bin/php-cs-fixer fix`
  - [ ] PHPStan: `vendor/bin/phpstan analyse` (level 9)
  - [ ] Brak linter errors

- [ ] **Funkcjonalność**:
  - [ ] Test manualny curl działa poprawnie
  - [ ] Endpoint zwraca 200 z poprawnymi danymi
  - [ ] Endpoint zwraca 401 dla nieprawidłowego tokena

### Krok 9: Deployment checklist

**Przed wdrożeniem na produkcję**:

- [ ] **Environment**:
  - [ ] Zmienne `.env` są ustawione (JWT keys, database)
  - [ ] JWT klucze (private.pem, public.pem) są wygenerowane i secure
  - [ ] `APP_ENV=prod` i `APP_DEBUG=0`

- [ ] **HTTPS**:
  - [ ] Certyfikat SSL jest zainstalowany
  - [ ] HTTP przekierowuje na HTTPS (301)
  - [ ] HSTS header jest włączony

- [ ] **CORS**:
  - [ ] Nelmio CORS bundle jest skonfigurowany
  - [ ] `allow_origin` zawiera tylko zaufane domeny (frontend)
  - [ ] `allow_headers` zawiera `Authorization`

- [ ] **Performance**:
  - [ ] Opcache PHP jest włączony
  - [ ] Doctrine cache jest skonfigurowany (Redis/Memcached)
  - [ ] Database indexes są utworzone (migracje uruchomione)

- [ ] **Monitoring**:
  - [ ] Logi błędów są zbierane (Monolog → file/Sentry)
  - [ ] Metryki są monitorowane (response time, error rate)
  - [ ] Alerty są skonfigurowane dla błędów 5xx

- [ ] **Security**:
  - [ ] Rate limiting jest włączony (opcjonalnie)
  - [ ] Firewall jest poprawnie skonfigurowany
  - [ ] Security headers są ustawione (X-Frame-Options, X-Content-Type-Options)

## 10. Podsumowanie

Endpoint `GET /api/v1/auth/me` jest prostym, bezpiecznym endpointem do pobierania profilu aktualnie zalogowanego użytkownika. 

**Kluczowe punkty**:
- ✅ **Brak logiki biznesowej** - operacja odczytu
- ✅ **Bezpieczeństwo**: JWT authentication przez Lexik Bundle
- ✅ **Prosta implementacja**: Tylko kontroler (bez command/handler)
- ✅ **Istniejące komponenty**: UserDto jest już gotowy
- ✅ **Automatyczna walidacja**: Security Layer obsługuje wszystko

**Szacowany czas implementacji**: 1-2 godziny (kod + testy + dokumentacja)

**Zależności**:
- Lexik JWT Authentication Bundle (✅ zainstalowany)
- Security configuration (✅ skonfigurowany)
- User entity (✅ istniejący)
- UserDto (✅ istniejący)

**Gotowość do wdrożenia**: Po implementacji kontrolera i testów, endpoint jest gotowy do użycia.

