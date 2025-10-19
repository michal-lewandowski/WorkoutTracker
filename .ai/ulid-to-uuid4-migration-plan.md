# Plan migracji: ULID → UUID4

**Status:** Gotowy do wykonania  
**Data:** 2025-10-11  
**Środowisko:** Tylko lokalne (bez danych produkcyjnych)

## Kontekst

Zmiana strategii identyfikatorów z ULID na UUID4 (UUID wersja 4 - losowy).

**Dlaczego można być agresywnym:**
- ✅ Aplikacja działa tylko lokalnie
- ✅ Brak danych produkcyjnych
- ✅ Można usunąć i stworzyć bazę danych od nowa
- ✅ Nie ma potrzeby migracji istniejących danych

---

## Plan wykonania

### Krok 1: Przygotowanie - backup i czyszczenie

**1.1. Usunięcie istniejących migracji**
```bash
rm -rf migrations/*
```

**1.2. Usunięcie bazy danych (opcjonalne, ale bezpieczniejsze)**
```bash
docker exec workouttracker-php-1 php bin/console doctrine:database:drop --force --if-exists
docker exec workouttracker-php-1 php bin/console doctrine:database:create
```

---

### Krok 2: Modyfikacja encji - zamiana ULID na UUID4

Wszystkie encje do zmiany (6 plików):
1. `src/Domain/Entity/User.php`
2. `src/Domain/Entity/Exercise.php`
3. `src/Domain/Entity/MuscleCategory.php`
4. `src/Domain/Entity/WorkoutSession.php`
5. `src/Domain/Entity/WorkoutExercise.php`
6. `src/Domain/Entity/ExerciseSet.php`

#### Zmiany w każdej encji:

**A) Import - zmiana z Ulid na Uuid**
```php
// PRZED:
use Symfony\Component\Uid\Ulid;

// PO:
use Symfony\Component\Uid\Uuid;
```

**B) Anotacja kolumny - zmiana typu z 'ulid' na 'uuid'**
```php
// PRZED:
#[ORM\Column(type: 'ulid', unique: true)]
private string $id;

// PO:
#[ORM\Column(type: 'uuid', unique: true)]
private string $id;
```

**C) Generowanie ID - zmiana z Ulid na Uuid::v4()**
```php
// PRZED:
$this->id = (string) new Ulid();

// PO:
$this->id = (string) Uuid::v4();
```

#### Szczegółowa lista zmian:

**User.php** (linie: 24, 32, 53)
- Import: `use Symfony\Component\Uid\Uuid;`
- Anotacja: `#[ORM\Column(type: 'uuid', unique: true)]`
- Konstruktor: `$this->id = (string) Uuid::v4();`

**Exercise.php** (linie: 22, 31, 58)
- Import: `use Symfony\Component\Uid\Uuid;`
- Anotacja: `#[ORM\Column(type: 'uuid', unique: true)]`
- Konstruktor: `$this->id = (string) Uuid::v4();`

**MuscleCategory.php** (linie: 22, 31, 50)
- Import: `use Symfony\Component\Uid\Uuid;`
- Anotacja: `#[ORM\Column(type: 'uuid', unique: true)]`
- Konstruktor: `$this->id = (string) Uuid::v4();`

**WorkoutSession.php** (linie: 22, 32, 70)
- Import: `use Symfony\Component\Uid\Uuid;`
- Anotacja: `#[ORM\Column(type: 'uuid', unique: true)]`
- Konstruktor: `$this->id = (string) Uuid::v4();`

**WorkoutExercise.php** (linie: 22, 31, 52)
- Import: `use Symfony\Component\Uid\Uuid;`
- Anotacja: `#[ORM\Column(type: 'uuid', unique: true)]`
- Konstruktor: `$this->id = (string) Uuid::v4();`

**ExerciseSet.php** (linie: 20, 28, 53)
- Import: `use Symfony\Component\Uid\Uuid;`
- Anotacja: `#[ORM\Column(type: 'uuid', unique: true)]`
- Konstruktor: `$this->id = (string) Uuid::v4();`

---

### Krok 3: Modyfikacja kontrolerów i walidacji (jeśli istnieją)

Sprawdzić pliki, które mogą zawierać walidację ULID:
- `src/Infrastructure/Controller/Exercise/GetExerciseByIdController.php`
- `src/Infrastructure/Api/Input/GetExercisesQueryDto.php`

**Jeśli jest walidacja ULID, zmienić na UUID:**
```php
// PRZED:
#[Assert\Ulid(message: 'Invalid ID format')]

// PO:
#[Assert\Uuid(message: 'Invalid ID format')]
```

---

### Krok 4: Wygenerowanie nowych migracji

```bash
docker exec workouttracker-php-1 php bin/console doctrine:migrations:generate
docker exec workouttracker-php-1 php bin/console doctrine:migrations:diff
```

**Ważne:** W nowo wygenerowanej migracji sprawdź, czy:
- Kolumny ID są typu `UUID NOT NULL`
- Komentarze Doctrine to `(DC2Type:uuid)` zamiast `(DC2Type:ulid)`
- Foreign keys są również typu UUID

---

### Krok 5: Wykonanie migracji

```bash
docker exec workouttracker-php-1 php bin/console doctrine:migrations:migrate --no-interaction
```

---

### Krok 6: Weryfikacja

**6.1. Sprawdzenie struktury bazy danych**
```bash
docker exec workouttracker-php-1 php bin/console doctrine:schema:validate
```

Powinno wyświetlić: `[OK] The database schema is in sync with the mapping files.`

**6.2. Test utworzenia użytkownika przez CLI**
```bash
docker exec workouttracker-php-1 php bin/console app:create-user test@example.com password123
```

**6.3. Sprawdzenie ID w bazie**
```bash
docker exec workouttracker-postgres-1 psql -U workout_user -d workout_db -c "SELECT id, email FROM users;"
```

Powinno wyświetlić UUID4 w formacie: `xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx`

**6.4. Test endpointów API**
```bash
# Test rejestracji
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test2@example.com","password":"password123"}'

# Test pobrania kategorii mięśni
curl -X GET http://localhost:8000/api/muscle-categories
```

---

### Krok 7: Uruchomienie testów

```bash
# Testy jednostkowe
docker exec workouttracker-php-1 php bin/phpunit tests/Unit

# Testy funkcjonalne
docker exec workouttracker-php-1 php bin/phpunit tests/Functional
```

**Testy mogą wymagać aktualizacji**, jeśli zawierają:
- Hardkodowane przykłady ULID
- Assertacje formatu ULID
- Mock ULID w fixture'ach

---

## Różnice: ULID vs UUID4

| Aspekt | ULID | UUID4 |
|--------|------|-------|
| Format | `01HQRS7GXNVP8K3WY2E6ZJ9MT4` (26 znaków) | `550e8400-e29b-41d4-a716-446655440000` (36 znaków) |
| Sortowanie | ✅ Chronologiczne (zawiera timestamp) | ❌ Losowe |
| Długość | 26 znaków (128 bit) | 36 znaków (128 bit) |
| Czytelność | Bardziej kompaktowy | Standardowy, z myślnikami |
| Unikość | Wysoka (timestamp + losowość) | Wysoka (pełna losowość) |
| Wydajność indeksów | ✅ Lepsza (sekwencyjne) | ❌ Gorsza (losowe) |
| Standard | Nowszy (2019) | Starszy, powszechny (RFC 4122) |
| Wsparcie | Mniejsze (nowsze narzędzia) | ✅ Uniwersalne |

**Dlaczego UUID4?**
- ✅ Bardziej popularny i powszechnie wspierany
- ✅ Lepsza kompatybilność z zewnętrznymi systemami
- ✅ Większa znajomość w community
- ⚠️ Gorsza wydajność indeksów (ale dla MVP bez znaczenia)

---

## Potencjalne problemy i rozwiązania

### Problem 1: Testy zawierają przykłady ULID
**Rozwiązanie:** Zamień przykłady na UUID4:
```php
// PRZED:
$userId = '01HQRS7GXNVP8K3WY2E6ZJ9MT4';

// PO:
$userId = '550e8400-e29b-41d4-a716-446655440000';
// lub wygeneruj nowy:
$userId = (string) \Symfony\Component\Uid\Uuid::v4();
```

### Problem 2: Frontend spodziewa się ULID
**Rozwiązanie:** Frontend jeszcze nie istnieje (tylko szkielet), więc nie ma problemu.

### Problem 3: Dokumentacja API (Swagger)
**Rozwiązanie:** Zaktualizuj `docs/swagger.json` - zamień przykłady ULID na UUID4.

### Problem 4: Niekompatybilne dane w bazie
**Rozwiązanie:** Usunąć bazę i stworzyć od nowa (dopuszczalne, bo brak danych produkcyjnych).

---

## Checklist wykonania

- [ ] **Krok 1:** Backup projektu (opcjonalny, ale zalecany)
- [ ] **Krok 2:** Usunięcie istniejących migracji `rm -rf migrations/*`
- [ ] **Krok 3:** Zmiana `User.php` (import, anotacja, konstruktor)
- [ ] **Krok 4:** Zmiana `Exercise.php` (import, anotacja, konstruktor)
- [ ] **Krok 5:** Zmiana `MuscleCategory.php` (import, anotacja, konstruktor)
- [ ] **Krok 6:** Zmiana `WorkoutSession.php` (import, anotacja, konstruktor)
- [ ] **Krok 7:** Zmiana `WorkoutExercise.php` (import, anotacja, konstruktor)
- [ ] **Krok 8:** Zmiana `ExerciseSet.php` (import, anotacja, konstruktor)
- [ ] **Krok 9:** Sprawdzenie kontrolerów pod kątem walidacji ULID
- [ ] **Krok 10:** Usunięcie bazy danych `doctrine:database:drop --force`
- [ ] **Krok 11:** Utworzenie bazy danych `doctrine:database:create`
- [ ] **Krok 12:** Wygenerowanie nowej migracji `doctrine:migrations:diff`
- [ ] **Krok 13:** Wykonanie migracji `doctrine:migrations:migrate`
- [ ] **Krok 14:** Walidacja schematu `doctrine:schema:validate`
- [ ] **Krok 15:** Test utworzenia użytkownika przez CLI
- [ ] **Krok 16:** Test endpointów API
- [ ] **Krok 17:** Uruchomienie testów jednostkowych
- [ ] **Krok 18:** Uruchomienie testów funkcjonalnych
- [ ] **Krok 19:** Aktualizacja dokumentacji Swagger (jeśli potrzebna)
- [ ] **Krok 20:** Commit zmian do Git

---

## Oszacowanie czasu

- Modyfikacja 6 encji: **~10 minut**
- Sprawdzenie kontrolerów: **~5 minut**
- Regeneracja migracji: **~2 minuty**
- Testy i weryfikacja: **~10 minut**

**Łącznie: ~30 minut**

---

## Polecenia do skopiowania

```bash
# Krok 1: Backup (opcjonalnie)
git add .
git commit -m "Backup before ULID to UUID4 migration"

# Krok 2: Usunięcie migracji
rm -rf migrations/*

# Krok 3-8: Edycja encji (ręcznie lub przez IDE)

# Krok 9: Usunięcie i utworzenie bazy
docker exec workouttracker-php-1 php bin/console doctrine:database:drop --force --if-exists
docker exec workouttracker-php-1 php bin/console doctrine:database:create

# Krok 10: Wygenerowanie migracji
docker exec workouttracker-php-1 php bin/console doctrine:migrations:diff

# Krok 11: Wykonanie migracji
docker exec workouttracker-php-1 php bin/console doctrine:migrations:migrate --no-interaction

# Krok 12: Walidacja
docker exec workouttracker-php-1 php bin/console doctrine:schema:validate

# Krok 13: Test CLI
docker exec workouttracker-php-1 php bin/console app:create-user test@example.com password123

# Krok 14: Testy
docker exec workouttracker-php-1 php bin/phpunit

# Krok 15: Commit
git add .
git commit -m "feat: migrate from ULID to UUID4 identifiers"
```

---

## Notatki

- UUID4 jest wersją z pełną losowością (w przeciwieństwie do UUID1 z timestamp)
- Symfony Uid component wspiera zarówno ULID jak i UUID
- PostgreSQL natywnie wspiera typ UUID
- Doctrine automatycznie konwertuje Symfony\Uuid na PostgreSQL UUID

---

**Gotowe do wykonania! 🚀**

