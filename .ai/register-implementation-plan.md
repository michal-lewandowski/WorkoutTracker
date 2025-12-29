# API Endpoint Implementation Plan: User Registration

## 1. Przegląd punktu końcowego

Endpoint służy do rejestracji nowego użytkownika w systemie WorkoutTracker. Przyjmuje email i hasło, waliduje dane, tworzy konto użytkownika w bazie danych PostgreSQL i zwraca token JWT wraz z danymi użytkownika. Jest to publiczny endpoint (bez wymaganej autoryzacji), będący punktem wejścia dla nowych użytkowników aplikacji.

**Kluczowe funkcjonalności**:
- Walidacja danych wejściowych (email, hasło)
- Sprawdzenie unikalności emaila (case-insensitive)
- Hashowanie hasła algorytmem bcrypt/argon2
- Utworzenie encji User z automatycznym uuid4
- Generowanie tokenu JWT (ważność 24h)
- Zwrot danych użytkownika + token w odpowiedzi

## 2. Szczegóły żądania

- **Metoda HTTP**: `POST`
- **Struktura URL**: `/api/v1/auth/register`
- **Content-Type**: `application/json`
- **Autoryzacja**: Brak (publiczny endpoint)

### Parametry

**Wymagane** (wszystkie w request body):
- `email` (string)
  - Format: email (RFC 5322)
  - Max długość: 255 znaków
  - Musi być unikalny w systemie (case-insensitive)
  - Przykład: `"user@example.com"`
  
- `password` (string)
  - Min długość: 8 znaków
  - Wymagania: przynajmniej 1 wielka litera + 1 cyfra
  - Pattern: `^(?=.*[A-Z])(?=.*\d).+$`
  - Przykład: `"SecurePass123"`
  
- `passwordConfirmation` (string)
  - Musi być identyczny z `password`
  - Przykład: `"SecurePass123"`

**Opcjonalne**: Brak

### Request Body (przykład)

```json
{
  "email": "john.doe@example.com",
  "password": "SecurePass123",
  "passwordConfirmation": "SecurePass123"
}
```

## 3. Wykorzystywane typy

**Struktura katalogów dla implementacji**:
```
src/
├── Application/
│   ├── Command/
│   │   └── Auth/
│   │       └── RegisterUserCommand.php
│   └── Exception/
│       └── EmailAlreadyExistsException.php
├── Domain/
│   ├── Entity/
│   │   └── User.php (już istnieje)
│   ├── Repository/
│   │   └── UserRepositoryInterface.php
│   └── Service/
│       └── UserRegistrationServiceInterface.php
└── Infrastructure/
    ├── Api/
    │   ├── Input/
    │   │   └── RegisterRequestDto.php
    │   └── Output/
    │       ├── UserDto.php
    │       ├── AuthResponseDto.php
    │       └── ValidationErrorDto.php
    ├── Controller/
    │   └── Auth/
    │       └── RegisterController.php
    ├── Repository/
    │   └── UserRepository.php
    └── Service/
        └── Auth/
            └── UserRegistrationService.php (implementuje UserRegistrationServiceInterface)
```

### Input DTOs (Request)

#### RegisterRequestDto
**Plik**: `src/Infrastructure/Api/Input/RegisterRequestDto.php`
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'Invalid email format')]
        #[Assert\Length(max: 255, maxMessage: 'Email cannot be longer than {{ limit }} characters')]
        public string $email,
        
        #[Assert\NotBlank(message: 'Password is required')]
        #[Assert\Length(
            min: 8,
            minMessage: 'Password must be at least {{ limit }} characters long'
        )]
        #[Assert\Regex(
            pattern: '/^(?=.*[A-Z])(?=.*\d).+$/',
            message: 'Password must contain at least 1 uppercase letter and 1 digit'
        )]
        public string $password,
        
        #[Assert\NotBlank(message: 'Password confirmation is required')]
        #[Assert\IdenticalTo(
            propertyPath: 'password',
            message: 'Password confirmation does not match password'
        )]
        public string $passwordConfirmation,
    ) {}
}
```

### Output DTOs (Response)

#### UserDto
**Plik**: `src/Infrastructure/Api/Output/UserDto.php`
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

use App\Domain\Entity\User;

final readonly class UserDto
{
    public function __construct(
        public string $id,
        public string $email,
        public \DateTimeImmutable $createdAt,
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

#### AuthResponseDto
**Plik**: `src/Infrastructure/Api/Output/AuthResponseDto.php`
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

final readonly class AuthResponseDto
{
    public function __construct(
        public UserDto $user,
        public string $token,
    ) {}
}
```

#### ValidationErrorDto
**Plik**: `src/Infrastructure/Api/Output/ValidationErrorDto.php`
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Output;

final readonly class ValidationErrorDto
{
    public function __construct(
        public string $message,
        public array $errors,
    ) {}
}
```

### Command Models

#### RegisterUserCommand
**Plik**: `src/Application/Command/Auth/RegisterUserCommand.php`
```php
<?php

declare(strict_types=1);

namespace App\Application\Command\Auth;

use App\Infrastructure\Api\Input\RegisterRequestDto;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $plainPassword,
    ) {}
    
    public static function fromDto(RegisterRequestDto $dto): self
    {
        return new self(
            email: $dto->email,
            plainPassword: $dto->password,
        );
    }
}
```

### Service Interfaces (Domain)

#### UserRegistrationServiceInterface
**Plik**: `src/Domain/Service/UserRegistrationServiceInterface.php`
```php
<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Application\Command\Auth\RegisterUserCommand;
use App\Infrastructure\Api\Output\AuthResponseDto;

interface UserRegistrationServiceInterface
{
    public function register(RegisterUserCommand $command): AuthResponseDto;
}
```

## 4. Szczegóły odpowiedzi

### Success Response (201 Created)

```json
{
  "user": {
    "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
    "email": "john.doe@example.com",
    "createdAt": "2025-10-11T10:30:00Z"
  },
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIwMUFSWjNOREVLVFNWNFJSRkZRNjlHNUZBViIsImVtYWlsIjoiam9obi5kb2VAZXhhbXBsZS5jb20iLCJpYXQiOjE2OTczNjQwMDAsImV4cCI6MTY5NzQ1MDQwMH0.abc123..."
}
```

**Struktura**:
- `user` (object): Dane użytkownika
  - `id` (string, uuid4): Unikalny identyfikator użytkownika
  - `email` (string): Adres email (lowercase)
  - `createdAt` (string, ISO 8601): Data utworzenia konta w UTC
- `token` (string): JWT access token (ważny 24h)

### Error Response (400 Bad Request)

```json
{
  "message": "Validation failed",
  "errors": {
    "email": [
      "Email is already registered"
    ],
    "password": [
      "Password must contain at least 1 uppercase letter and 1 digit"
    ],
    "passwordConfirmation": [
      "Password confirmation does not match password"
    ]
  }
}
```

**Struktura**:
- `message` (string): Ogólna wiadomość o błędzie
- `errors` (object): Mapa pól z listą błędów walidacji
  - Klucze: nazwy pól z request body
  - Wartości: tablice komunikatów błędów dla danego pola

### Kody statusu HTTP

| Kod | Znaczenie | Kiedy występuje |
|-----|-----------|-----------------|
| 201 | Created | Użytkownik pomyślnie zarejestrowany |
| 400 | Bad Request | Błędy walidacji (format email, słabe hasło, email już istnieje) |
| 500 | Internal Server Error | Błąd serwera (baza danych, generowanie tokenu) |

## 5. Przepływ danych

### 5.1 Architektura warstwowa

```
┌─────────────────────────────────────────────────────────┐
│             Infrastructure Layer                        │
│  ┌──────────────────────────────────────────────────┐  │
│  │ RegisterController                                │  │
│  │ - Obsługa HTTP request/response                  │  │
│  │ - Walidacja input (RegisterRequestDto)           │  │
│  │ - Wywołanie Domain Service (przez interfejs)     │  │
│  └──────────────────────────────────────────────────┘  │
│                         ↓                               │
│  ┌──────────────────────────────────────────────────┐  │
│  │ UserRegistrationService                           │  │
│  │ - Implementacja UserRegistrationServiceInterface │  │
│  │ - Obsługa JWT, password hashing                  │  │
│  │ - Orchestracja procesu rejestracji               │  │
│  └──────────────────────────────────────────────────┘  │
│                         ↓                               │
│  ┌──────────────────────────────────────────────────┐  │
│  │ UserRepository                                    │  │
│  │ - Implementacja UserRepositoryInterface          │  │
│  │ - Komunikacja z bazą danych (Doctrine)           │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                         ↕
┌─────────────────────────────────────────────────────────┐
│             Domain Layer                                │
│  ┌──────────────────────────────────────────────────┐  │
│  │ UserRegistrationServiceInterface                  │  │
│  │ - Kontrakt dla serwisu rejestracji               │  │
│  └──────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────┐  │
│  │ UserRepositoryInterface                           │  │
│  │ - Kontrakt dla repozytorium                      │  │
│  └──────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────┐  │
│  │ User Entity                                       │  │
│  │ - Logika biznesowa użytkownika                   │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                         ↕
┌─────────────────────────────────────────────────────────┐
│             Application Layer                           │
│  ┌──────────────────────────────────────────────────┐  │
│  │ RegisterUserCommand                               │  │
│  │ - Transfer obiekt dla komendy rejestracji        │  │
│  └──────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────┐  │
│  │ EmailAlreadyExistsException                       │  │
│  │ - Wyjątki domenowe                                │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

**Zasady architektury**:
- **Domain** nie zależy od żadnej warstwy (czysta logika biznesowa)
- **Application** zależy tylko od Domain (use cases, commands)
- **Infrastructure** zależy od Domain i Application (implementacje, adaptery)
- **Dependency Inversion**: Controller zależy od interfejsu `UserRegistrationServiceInterface`, nie od konkretnej implementacji

### 5.2 Sekwencja działań

```
1. Controller (Infrastructure) otrzymuje HTTP POST request
   ↓
2. Symfony deserializuje JSON → RegisterRequestDto (Infrastructure/Api/Input)
   ↓
3. Symfony Validator waliduje DTO (constraints)
   ↓
4. Controller tworzy RegisterUserCommand (Application) z DTO
   ↓
5. Controller wywołuje UserRegistrationServiceInterface->register()
   ↓
6. UserRegistrationService (Infrastructure) implementuje interfejs z Domain
   ↓
7. Service sprawdza unikalność emaila przez UserRepositoryInterface (Domain)
   ↓
8. Service hashuje hasło (UserPasswordHasherInterface - Symfony)
   ↓
9. Service tworzy encję User::create(email) (Domain Entity)
   ↓
10. Service ustawia passwordHash w encji
   ↓
11. Service persists User przez UserRepositoryInterface (Domain → Infrastructure)
   ↓
12. Service generuje JWT token (LexikJWTAuthenticationBundle)
   ↓
13. Service zwraca AuthResponseDto (Infrastructure/Api/Output)
   ↓
14. Controller serializuje odpowiedź do JSON
   ↓
15. HTTP 201 response z user + token
```

### 5.3 Interakcja z bazą danych

**Tabele**:
- `users` - zapis nowego rekordu użytkownika

**Operacje**:
1. **SELECT** - sprawdzenie czy email już istnieje
   ```sql
   SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1
   ```

2. **INSERT** - utworzenie nowego użytkownika
   ```sql
   INSERT INTO users (id, email, password_hash, created_at, updated_at)
   VALUES (:id, :email, :password_hash, :created_at, :updated_at)
   ```

**Indeksy wykorzystywane**:
- `idx_users_email_lower` - przyspieszenie wyszukiwania po emailu (case-insensitive)

### 5.4 Interakcja z zewnętrznymi usługami

- **LexikJWTAuthenticationBundle**: Generowanie tokenu JWT
  - Input: User object
  - Output: JWT string (payload: user id, email, iat, exp)
  - TTL: 24 godziny (konfiguracja w `config/packages/lexik_jwt_authentication.yaml`)

## 6. Względy bezpieczeństwa

### 6.1 Uwierzytelnianie i Autoryzacja

- **Uwierzytelnianie**: Endpoint jest publiczny, nie wymaga tokenu JWT
- **Autoryzacja**: Brak (każdy może się zarejestrować)
- **Rate Limiting**: **ZALECANE** - ograniczenie liczby prób rejestracji z jednego IP (np. 5 prób / 15 minut)

### 6.2 Walidacja danych wejściowych

#### Poziom DTO (Input Validation)
- Email: format, długość, required
- Password: długość, złożoność (pattern), required
- PasswordConfirmation: zgodność z password

#### Poziom domeny (Business Rules)
- Unikalność emaila (query do bazy)
- Normalizacja emaila do lowercase
- Walidacja siły hasła (regex)

### 6.3 Zabezpieczenia haseł

- **Hashowanie**: Użycie `UserPasswordHasherInterface` (Symfony Security)
- **Algorytm**: bcrypt lub argon2 (konfiguracja w `config/packages/security.yaml`)
- **Salt**: Automatycznie dodawany przez Symfony
- **Nigdy nie loguj**: Hasła w plain text nie mogą być zapisywane w logach ani response

```php
$hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
```

### 6.4 Bezpieczeństwo bazy danych

- **SQL Injection**: Zabezpieczone przez Doctrine ORM (parameterized queries)
- **Case-insensitive search**: Użycie LOWER() w zapytaniach
- **Index na email**: Zapobiega duplicate entries (unique constraint)

### 6.5 JWT Token

- **Payload**: user id (uuid4), email, iat (issued at), exp (expiration)
- **Expiration**: 24 godziny
- **Algorithm**: RS256 lub HS256 (konfiguracja)
- **Secret/Keys**: Przechowywane w `.env` (nigdy w repo)

### 6.6 CORS

- Konfiguracja w `config/packages/nelmio_cors.yaml`
- Zezwolenie na POST request z frontend origin
- Proper headers: `Content-Type: application/json`

### 6.7 Nie loguj wrażliwych danych

```php
// ✅ GOOD
$this->logger->info('User registration attempt', ['email' => $email]);

// ❌ BAD
$this->logger->info('User registration', ['password' => $password]);
```

## 7. Obsługa błędów

### 7.1 Błędy walidacji (400 Bad Request)

| Pole | Warunek | Komunikat |
|------|---------|-----------|
| email | Puste | "Email is required" |
| email | Nieprawidłowy format | "Invalid email format" |
| email | Za długi (>255) | "Email cannot be longer than 255 characters" |
| email | Już istnieje w bazie | "Email is already registered" |
| password | Puste | "Password is required" |
| password | Za krótkie (<8) | "Password must be at least 8 characters long" |
| password | Brak wielkiej litery lub cyfry | "Password must contain at least 1 uppercase letter and 1 digit" |
| passwordConfirmation | Puste | "Password confirmation is required" |
| passwordConfirmation | Nie pasuje do password | "Password confirmation does not match password" |

**Obsługa w kodzie**:
```php
// Custom validation for email uniqueness
if ($this->userRepository->findByEmail($command->email)) {
    throw new ValidationException([
        'email' => ['Email is already registered']
    ]);
}
```

### 7.2 Błędy serwera (500 Internal Server Error)

| Scenariusz | Akcja |
|------------|-------|
| Database connection failure | Log error + return generic 500 message |
| JWT token generation failure | Log error + return 500 with message "Failed to generate authentication token" |
| Unexpected exception | Log full stack trace + return generic 500 message |

**Przykład obsługi**:
```php
try {
    $token = $this->jwtManager->create($user);
} catch (\Exception $e) {
    $this->logger->error('JWT token generation failed', [
        'user_id' => $user->getId(),
        'error' => $e->getMessage()
    ]);
    throw new \RuntimeException('Failed to generate authentication token');
}
```

### 7.3 Exception Hierarchy

```
\Exception
  ├── DomainException (domain logic violations)
  │     └── EmailAlreadyExistsException
  ├── ValidationException (validation failures)
  └── \RuntimeException (infrastructure failures)
        ├── DatabaseException
        └── JwtGenerationException
```

### 7.4 Logging Strategy

```php
// Registration attempt
$this->logger->info('User registration attempt', [
    'email' => $email,
    'ip' => $request->getClientIp()
]);

// Registration success
$this->logger->info('User registered successfully', [
    'user_id' => $user->getId(),
    'email' => $user->getEmail()
]);

// Registration failure
$this->logger->warning('User registration failed', [
    'email' => $email,
    'reason' => 'Email already exists'
]);

// Server error
$this->logger->error('Registration process error', [
    'email' => $email,
    'error' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString()
]);
```

## 8. Rozważania dotyczące wydajności

### 8.1 Potencjalne wąskie gardła

1. **Sprawdzenie unikalności emaila**
   - Query do bazy danych przy każdej rejestracji
   - Potencjalnie powolne przy dużej liczbie użytkowników
   - **Optymalizacja**: Index na kolumnie `email` (już istnieje: `idx_users_email_lower`)

2. **Hashowanie hasła**
   - Bcrypt/Argon2 są celowo powolne (bezpieczeństwo)
   - ~200-300ms na hash
   - **To jest OK** - security > performance dla tej operacji

3. **Generowanie JWT tokenu**
   - Relatywnie szybkie (~5-10ms)
   - Nie stanowi wąskiego gardła

### 8.2 Strategie optymalizacji

#### Database Query Optimization
```php
// Użyj EXISTS zamiast SELECT COUNT/fetch
// Szybsze dla large datasets
$exists = $this->entityManager->createQueryBuilder()
    ->select('1')
    ->from(User::class, 'u')
    ->where('LOWER(u.email) = LOWER(:email)')
    ->setParameter('email', $email)
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();
```

#### Caching (przyszłość)
- Dla MVP: brak cache
- W przyszłości: Cache negatywnych wyników (email nie istnieje) z TTL 5 minut

#### Connection Pooling
- PostgreSQL connection pooling (PgBouncer) - konfiguracja na poziomie infrastruktury
- Doctrine connection persistence

### 8.3 Monitoring i metryki

**Kluczowe metryki do śledzenia**:
- Response time (target: <500ms dla 95th percentile)
- Registration success rate
- Failed registration attempts (by reason)
- Database query time
- JWT generation time

### 8.4 Scalability

- **Vertical**: OK dla MVP (single server)
- **Horizontal**: Stateless endpoint - łatwo skalowalny
- **Database**: Primary-Replica setup możliwy w przyszłości (write do primary, read checks z replica)

## 9. Etapy wdrożenia

### Krok 1: Utworzenie struktury katalogów
```bash
mkdir -p src/Infrastructure/Api/Input
mkdir -p src/Infrastructure/Api/Output
mkdir -p src/Infrastructure/Controller/Auth
mkdir -p src/Infrastructure/Service/Auth
mkdir -p src/Infrastructure/Repository
mkdir -p src/Application/Command/Auth
mkdir -p src/Application/Exception
mkdir -p src/Domain/Repository
mkdir -p src/Domain/Service
```

### Krok 2: Implementacja Repository Interface
**Plik**: `src/Domain/Repository/UserRepositoryInterface.php`
```php
<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findByEmail(string $email): ?User;
}
```

### Krok 3: Implementacja Repository
**Plik**: `src/Infrastructure/Repository/UserRepository.php`
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
    
    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
    
    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('LOWER(u.email) = LOWER(:email)')
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
```

### Krok 4: Utworzenie DTOs
**Pliki**:
- `src/Infrastructure/Api/Input/RegisterRequestDto.php` (patrz sekcja 3)
- `src/Infrastructure/Api/Output/UserDto.php` (patrz sekcja 3)
- `src/Infrastructure/Api/Output/AuthResponseDto.php` (patrz sekcja 3)
- `src/Infrastructure/Api/Output/ValidationErrorDto.php` (patrz sekcja 3)

### Krok 5: Utworzenie Command
**Plik**: `src/Application/Command/Auth/RegisterUserCommand.php` (patrz sekcja 3)

### Krok 6: Utworzenie Exception
**Plik**: `src/Application/Exception/EmailAlreadyExistsException.php`
```php
<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class EmailAlreadyExistsException extends \DomainException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('Email "%s" is already registered', $email));
    }
}
```

### Krok 7: Utworzenie Service Interface (Domain)
**Plik**: `src/Domain/Service/UserRegistrationServiceInterface.php` (patrz sekcja 3)

### Krok 8: Implementacja Service (Infrastructure)
**Plik**: `src/Infrastructure/Service/Auth/UserRegistrationService.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Auth;

use App\Application\Command\Auth\RegisterUserCommand;use App\Domain\Entity\User;use App\Domain\Exception\EmailAlreadyExistsException;use App\Domain\Repository\UserRepositoryInterface;use App\Domain\Service\UserRegistrationServiceInterface;use App\Infrastructure\Api\Output\AuthResponseDto;use App\Infrastructure\Api\Output\UserDto;use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;use Psr\Log\LoggerInterface;use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserRegistrationService implements UserRegistrationServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private LoggerInterface $logger,
    ) {}
    
    public function register(RegisterUserCommand $command): AuthResponseDto
    {
        $this->logger->info('User registration attempt', [
            'email' => $command->email
        ]);
        
        // Check if email already exists
        if ($this->userRepository->findByEmail($command->email)) {
            $this->logger->warning('Registration failed - email already exists', [
                'email' => $command->email
            ]);
            throw new EmailAlreadyExistsException($command->email);
        }
        
        // Create user entity
        $user = User::create($command->email);
        
        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $command->plainPassword
        );
        $user->setPasswordHash($hashedPassword);
        
        // Save to database
        try {
            $this->userRepository->save($user);
        } catch (\Exception $e) {
            $this->logger->error('Failed to save user to database', [
                'email' => $command->email,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to create user account');
        }
        
        // Generate JWT token
        try {
            $token = $this->jwtManager->create($user);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate JWT token', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to generate authentication token');
        }
        
        $this->logger->info('User registered successfully', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail()
        ]);
        
        return new AuthResponseDto(
            user: UserDto::fromEntity($user),
            token: $token
        );
    }
}
```

### Krok 9: Implementacja Controller
**Plik**: `src/Infrastructure/Controller/Auth/RegisterController.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Auth;

use App\Application\Command\Auth\RegisterUserCommand;use App\Domain\Exception\EmailAlreadyExistsException;use App\Domain\Service\UserRegistrationServiceInterface;use App\Infrastructure\Api\Input\RegisterRequestDto;use App\Infrastructure\Api\Output\ValidationErrorDto;use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;use Symfony\Component\HttpFoundation\JsonResponse;use Symfony\Component\HttpFoundation\Response;use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly UserRegistrationServiceInterface $registrationService,
    ) {}
    
    #[Route('/api/v1/auth/register', name: 'auth_register', methods: ['POST'])]
    public function register(
        #[MapRequestPayload] RegisterRequestDto $dto
    ): JsonResponse {
        try {
            $command = RegisterUserCommand::fromDto($dto);
            $authResponse = $this->registrationService->register($command);
            
            return $this->json($authResponse, Response::HTTP_CREATED);
            
        } catch (EmailAlreadyExistsException $e) {
            $error = new ValidationErrorDto(
                message: 'Validation failed',
                errors: ['email' => ['Email is already registered']]
            );
            return $this->json($error, Response::HTTP_BAD_REQUEST);
            
        } catch (\RuntimeException $e) {
            return $this->json([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
```

### Krok 10: Konfiguracja Services
**Plik**: `config/services.yaml`
```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Domain/Entity/'
            - '../src/Kernel.php'
    
    # Repository binding
    App\Domain\Repository\UserRepositoryInterface:
        class: App\Infrastructure\Repository\UserRepository
    
    # Service binding
    App\Domain\Service\UserRegistrationServiceInterface:
        class: App\Infrastructure\Service\Auth\UserRegistrationService
```

### Krok 11: Testy jednostkowe
**Plik**: `tests/Unit/Infrastructure/Service/Auth/UserRegistrationServiceTest.php`

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Service\Auth;

use App\Application\Command\Auth\RegisterUserCommand;use App\Domain\Entity\User;use App\Domain\Exception\EmailAlreadyExistsException;use App\Domain\Repository\UserRepositoryInterface;use App\Infrastructure\Service\Auth\UserRegistrationService;use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;use PHPUnit\Framework\TestCase;use Psr\Log\LoggerInterface;use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserRegistrationServiceTest extends TestCase
{
    private UserRepositoryInterface $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private JWTTokenManagerInterface $jwtManager;
    private LoggerInterface $logger;
    private UserRegistrationService $service;
    
    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->service = new UserRegistrationService(
            $this->userRepository,
            $this->passwordHasher,
            $this->jwtManager,
            $this->logger
        );
    }
    
    public function testSuccessfulRegistration(): void
    {
        $command = new RegisterUserCommand(
            email: 'test@test.com',
            plainPassword: 'SecurePass123'
        );
        
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@test.com')
            ->willReturn(null);
        
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('$hashed$password$');
        
        $this->userRepository
            ->expects($this->once())
            ->method('save');
        
        $this->jwtManager
            ->expects($this->once())
            ->method('create')
            ->willReturn('jwt.token.here');
        
        $result = $this->service->register($command);
        
        $this->assertSame('test@test.com', $result->user->email);
        $this->assertSame('jwt.token.here', $result->token);
    }
    
    public function testRegistrationFailsWhenEmailExists(): void
    {
        $command = new RegisterUserCommand(
            email: 'existing@example.com',
            plainPassword: 'SecurePass123'
        );
        
        $existingUser = User::create('existing@example.com');
        
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('existing@example.com')
            ->willReturn($existingUser);
        
        $this->expectException(EmailAlreadyExistsException::class);
        
        $this->service->register($command);
    }
}
```

### Krok 12: Testy funkcjonalne
**Plik**: `tests/Functional/Controller/Auth/RegisterControllerTest.php`
```php
<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class RegisterControllerTest extends WebTestCase
{
    public function testSuccessfulRegistration(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'newuser@example.com',
                'password' => 'SecurePass123',
                'passwordConfirmation' => 'SecurePass123'
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('token', $data);
        $this->assertSame('newuser@example.com', $data['user']['email']);
        $this->assertNotEmpty($data['token']);
    }
    
    public function testRegistrationFailsWithInvalidEmail(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'invalid-email',
                'password' => 'SecurePass123',
                'passwordConfirmation' => 'SecurePass123'
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('email', $data['errors']);
    }
    
    public function testRegistrationFailsWithWeakPassword(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'test@test.com',
                'password' => 'weak',
                'passwordConfirmation' => 'weak'
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('password', $data['errors']);
    }
    
    public function testRegistrationFailsWithMismatchedPasswords(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'test@test.com',
                'password' => 'SecurePass123',
                'passwordConfirmation' => 'DifferentPass456'
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('passwordConfirmation', $data['errors']);
    }
}
```

### Krok 13: Uruchomienie testów
```bash
# Unit tests
php bin/phpunit tests/Unit

# Functional tests
php bin/phpunit tests/Functional

# All tests
php bin/phpunit

# Code coverage
php bin/phpunit --coverage-html coverage/
```

### Krok 14: PHPStan - statyczna analiza kodu
```bash
vendor/bin/phpstan analyse src tests --level=9
```

### Krok 15: PHP CS Fixer - formatowanie kodu
```bash
vendor/bin/php-cs-fixer fix src/
vendor/bin/php-cs-fixer fix tests/
```

### Krok 16: Test manualny z cURL
```bash
# Success case
curl -X POST http://localhost/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@test.com",
    "password": "SecurePass123",
    "passwordConfirmation": "SecurePass123"
  }'

# Expected response (201):
# {
#   "user": {
#     "id": "01ARZ3NDEKTSV4RRFFQ69G5FAV",
#     "email": "test@test.com",
#     "createdAt": "2025-10-11T10:30:00+00:00"
#   },
#   "token": "eyJhbGci..."
# }

# Duplicate email case
curl -X POST http://localhost/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@test.com",
    "password": "SecurePass123",
    "passwordConfirmation": "SecurePass123"
  }'

# Expected response (400):
# {
#   "message": "Validation failed",
#   "errors": {
#     "email": ["Email is already registered"]
#   }
# }
```

### Krok 17: Weryfikacja w bazie danych
```sql
-- Check created user
SELECT id, email, created_at, updated_at 
FROM users 
WHERE email = 'test@test.com';

-- Verify password is hashed
SELECT LENGTH(password_hash) as hash_length 
FROM users 
WHERE email = 'test@test.com';
-- Should return ~60 for bcrypt or ~95 for argon2
```

---

## 10. Checklist przed wdrożeniem na produkcję

- [ ] Wszystkie testy jednostkowe przechodzą
- [ ] Wszystkie testy funkcjonalne przechodzą
- [ ] PHPStan level 9 bez błędów
- [ ] PHP CS Fixer zastosowany
- [ ] JWT klucze wygenerowane i zabezpieczone
- [ ] Zmienne środowiskowe skonfigurowane w `.env.local`
- [ ] CORS poprawnie skonfigurowany dla frontend domain
- [ ] Rate limiting zaimplementowany (opcjonalnie dla MVP)
- [ ] Monitoring i logging działają poprawnie
- [ ] Database index na `email` utworzony i zweryfikowany
- [ ] Dokumentacja API (Swagger) zaktualizowana
- [ ] Manual smoke test wykonany
- [ ] Code review przeprowadzony

---

## 11. Przydatne komendy

```bash
# Uruchomienie serwera deweloperskiego
symfony server:start

# Sprawdzenie routes
php bin/console debug:router | grep register

# Migracje bazy danych
php bin/console doctrine:migrations:migrate

# Cache clear
php bin/console cache:clear

# Generate JWT keys
php bin/console lexik:jwt:generate-keypair

# Test endpoint
curl -X POST http://localhost/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"SecurePass123","passwordConfirmation":"SecurePass123"}'
```

---

## 12. Uwagi dotyczące architektury

### 12.1 Zalety zastosowanej architektury

**Separation of Concerns (Rozdzielenie odpowiedzialności)**:
- DTOs w `Infrastructure/Api` - warstwa komunikacji z zewnętrznym światem
- Service Interfaces w `Domain` - kontrakty biznesowe niezależne od implementacji
- Service Implementations w `Infrastructure` - szczegóły techniczne (JWT, hashing)
- Commands w `Application` - use cases aplikacji

**Dependency Inversion Principle**:
```php
// Controller zależy od interfejsu, nie od konkretnej implementacji
public function __construct(
    private readonly UserRegistrationServiceInterface $registrationService,
) {}
```

Dzięki temu:
- Łatwa podmiana implementacji (np. mock w testach)
- Domain nie zależy od frameworka Symfony
- Możliwość testowania logiki biznesowej w izolacji

**Testability (Testowalność)**:
- Service można testować niezależnie od Controllera
- Łatwo mockować zależności przez interfejsy
- Domain logic oddzielona od framework logic

**Maintainability (Łatwość utrzymania)**:
- Jasna struktura katalogów (Input/Output DTOs)
- Łatwe znalezienie komponentów
- Każda warstwa ma jasno określoną odpowiedzialność

### 12.2 Best Practices zastosowane w planie

1. **Readonly classes** dla DTOs i Services - niemutowalne obiekty
2. **Constructor Property Promotion** - zwięzły kod PHP 8.4
3. **Named arguments** - czytelność przy tworzeniu obiektów
4. **Strict types** - bezpieczeństwo typów
5. **Interface segregation** - małe, wyspecjalizowane interfejsy
6. **Factory methods** - `UserDto::fromEntity()`, `RegisterUserCommand::fromDto()`

### 12.3 Rozszerzalność

Dzięki zastosowanej architekturze łatwo dodać:
- **Nowe źródła autentykacji** (OAuth, LDAP) - wystarczy nowa implementacja interfejsu
- **Nowe formaty response** (XML, GraphQL) - nowe DTOs w Infrastructure/Api/Output
- **Event-driven architecture** - Domain Events w Domain layer
- **CQRS** - oddzielenie Commands od Queries

---

## 13. Kontakt i wsparcie

W razie pytań lub problemów podczas implementacji:
1. Sprawdź logi aplikacji: `var/log/dev.log`
2. Sprawdź logi Symfony profiler: `http://localhost/_profiler`
3. Zweryfikuj konfigurację w `.env.local`
4. Przejrzyj dokumentację Symfony Security i LexikJWTAuthenticationBundle

**Dokumentacje referencyjne**:
- [Symfony Security](https://symfony.com/doc/current/security.html)
- [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle/blob/2.x/Resources/doc/index.rst)
- [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [Symfony Validation](https://symfony.com/doc/current/validation.html)

