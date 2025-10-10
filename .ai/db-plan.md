# Schemat bazy danych WorkoutTracker MVP

## Przegląd

Schemat bazy danych dla aplikacji WorkoutTracker - systemu śledzenia treningów oporowych. Projekt oparty na PostgreSQL, zoptymalizowany pod Symfony 7.3 (PHP 8.4) z wykorzystaniem Doctrine ORM.

### Kluczowe założenia projektowe
- **Klucze główne**: ULID (CHAR(26)) - sortowalne chronologicznie, generowane w aplikacji
- **Wielojęzyczność**: Przygotowanie pod i18n (polski domyślnie, angielski w przyszłości)
- **Soft delete**: Dla workout_sessions (możliwość przywracania)
- **Walidacja**: Logika biznesowa w aplikacji, nie w bazie danych
- **Skala MVP**: ~100 użytkowników, ~1000 sesji treningowych

---

## 1. Tabele z kolumnami, typami danych i ograniczeniami

### 1.1 `users`
Tabela przechowująca konta użytkowników aplikacji.

| Kolumna | Typ | Ograniczenia | Opis |
|---------|-----|--------------|------|
| `id` | CHAR(26) | PRIMARY KEY | ULID użytkownika |
| `email` | VARCHAR(255) | NOT NULL | Adres email (normalizowany do lowercase) |
| `password_hash` | VARCHAR(255) | NOT NULL | Hash hasła (bcrypt/argon2id) |
| `created_at` | TIMESTAMPTZ | NOT NULL DEFAULT CURRENT_TIMESTAMP | Data rejestracji |
| `updated_at` | TIMESTAMPTZ | NOT NULL DEFAULT CURRENT_TIMESTAMP | Data ostatniej aktualizacji |

**Ograniczenia dodatkowe**:
- Unique index na `LOWER(email)` - case-insensitive unikalność

---

### 1.2 `muscle_categories`
Słownik kategorii mięśniowych (6 rekordów stałych).

| Kolumna | Typ | Ograniczenia | Opis |
|---------|-----|--------------|------|
| `id` | CHAR(26) | PRIMARY KEY | ULID kategorii |
| `name_pl` | VARCHAR(100) | NOT NULL UNIQUE | Nazwa po polsku |
| `name_en` | VARCHAR(100) | NOT NULL UNIQUE | Nazwa po angielsku |
| `created_at` | TIMESTAMPTZ | NOT NULL DEFAULT CURRENT_TIMESTAMP | Data utworzenia |

**Dane seed** (6 kategorii):
1. Klatka piersiowa / Chest
2. Plecy / Back
3. Nogi / Legs
4. Barki / Shoulders
5. Biceps / Biceps
6. Triceps / Triceps

---

### 1.3 `exercises`
Słownik ćwiczeń oporowych (50-70 rekordów).

| Kolumna | Typ | Ograniczenia | Opis |
|---------|-----|--------------|------|
| `id` | CHAR(26) | PRIMARY KEY | ULID ćwiczenia |
| `name` | VARCHAR(255) | NOT NULL UNIQUE | Nazwa ćwiczenia (polski) |
| `name_en` | VARCHAR(255) | NULL | Nazwa angielska (NULL w MVP) |
| `muscle_category_id` | CHAR(26) | NOT NULL | FK do muscle_categories |
| `created_at` | TIMESTAMPTZ | NOT NULL DEFAULT CURRENT_TIMESTAMP | Data utworzenia |
| `updated_at` | TIMESTAMPTZ | NOT NULL DEFAULT CURRENT_TIMESTAMP | Data ostatniej aktualizacji |

**Foreign Keys**:
- `muscle_category_id` → `muscle_categories(id)` ON DELETE RESTRICT

**Walidacja aplikacji** (nie w bazie):
- Każda kategoria powinna zawierać 8-12 ćwiczeń
- Łącznie 50-70 ćwiczeń w słowniku

---

### 1.4 `workout_sessions`
Sesje treningowe użytkowników (soft delete).

| Kolumna | Typ | Ograniczenia | Opis |
|---------|-----|--------------|------|
| `id` | CHAR(26) | PRIMARY KEY | ULID sesji |
| `user_id` | CHAR(26) | NOT NULL | FK do users |
| `date` | DATE | NOT NULL DEFAULT CURRENT_DATE | Data sesji treningowej |
| `name` | VARCHAR(255) | NULL | Opcjonalna nazwa sesji (np. "Trening A") |
| `notes` | TEXT | NULL | Opcjonalne notatki użytkownika |
| `deleted_at` | TIMESTAMPTZ | NULL | Data soft delete (NULL = aktywna) |
| `deleted_by` | CHAR(26) | NULL | FK do users (kto usunął) |
| `created_at` | TIMESTAMPTZ | NOT NULL DEFAULT CURRENT_TIMESTAMP | Data utworzenia |
| `updated_at` | TIMESTAMPTZ | NOT NULL DEFAULT CURRENT_TIMESTAMP | Data ostatniej aktualizacji |

**Foreign Keys**:
- `user_id` → `users(id)` ON DELETE CASCADE
- `deleted_by` → `users(id)` ON DELETE SET NULL

**Uwagi**:
- Soft delete: sesje nie są fizycznie usuwane, tylko oznaczane `deleted_at`
- Przywrócenie sesji: ustawienie `deleted_at = NULL`, zachowanie oryginalnego `created_at`
- Brak unikalności dla `name` - użytkownik może mieć wiele sesji o tej samej nazwie

**Walidacja aplikacji**:
- Maksymalnie 15 ćwiczeń na sesję

---

### 1.5 `workout_exercises`
Ćwiczenia w ramach sesji treningowej (junction table).

| Kolumna | Typ | Ograniczenia | Opis |
|---------|-----|--------------|------|
| `id` | CHAR(26) | PRIMARY KEY | ULID wpisu |
| `workout_session_id` | CHAR(26) | NOT NULL | FK do workout_sessions |
| `exercise_id` | CHAR(26) | NOT NULL | FK do exercises |
| `created_at` | TIMESTAMPTZ | NOT NULL DEFAULT CURRENT_TIMESTAMP | Data dodania (używana do sortowania) |

**Foreign Keys**:
- `workout_session_id` → `workout_sessions(id)` ON DELETE CASCADE
- `exercise_id` → `exercises(id)` ON DELETE RESTRICT

**Uwagi**:
- Brak pola `order_in_session` - sortowanie po `created_at`
- To samo ćwiczenie może występować wielokrotnie w jednej sesji
- Fizyczne usunięcie przy kasowaniu sesji (CASCADE)

---

### 1.6 `exercise_sets`
Grupy serii dla ćwiczeń (optymalizacja zapisu).

| Kolumna | Typ | Ograniczenia | Opis |
|---------|-----|--------------|------|
| `id` | CHAR(26) | PRIMARY KEY | ULID grupy serii |
| `workout_exercise_id` | CHAR(26) | NOT NULL | FK do workout_exercises |
| `sets_count` | INTEGER | NOT NULL | Liczba serii (np. 3 w notacji 3x6@40kg) |
| `reps` | INTEGER | NOT NULL | Liczba powtórzeń |
| `weight_grams` | INTEGER | NOT NULL | Ciężar w gramach (0-500000) |
| `created_at` | TIMESTAMPTZ | NOT NULL DEFAULT CURRENT_TIMESTAMP | Data dodania (używana do sortowania) |

**Foreign Keys**:
- `workout_exercise_id` → `workout_exercises(id)` ON DELETE CASCADE

**Uwagi**:
- **Grupowanie serii**: 3x6@40kg = `sets_count=3, reps=6, weight_grams=40000` (1 rekord)
- **Ciężar w gramach**: INTEGER eliminuje floating point errors, zakres 0-500000 (0-500kg)
- **Konwersja**: kg ↔ gramy w warstwie aplikacji (Symfony)
- Brak pola `order_in_exercise` - sortowanie po `created_at`

**Walidacja aplikacji** (nie w bazie):
- `sets_count`: minimum 1 (praktycznie, brak constraint)
- `reps`: 1-100
- `weight_grams`: 0-500000 (0-500kg)
- Maksymalnie 20 serii na ćwiczenie (suma `sets_count`)

---

## 2. Relacje między tabelami

### Diagram relacji

```
users (1) ----< (N) workout_sessions (soft delete)
                         |
                         | (1)
                         |
                         v
                    (N) workout_exercises
                         |
                         | (1)
                         |
                         v
                    (N) exercise_sets

muscle_categories (1) ----< (N) exercises
                                    |
                                    | (1)
                                    |
                                    v
                               (N) workout_exercises
```

### Szczegółowy opis relacji

| Relacja | Typ | ON DELETE | Opis |
|---------|-----|-----------|------|
| `users` → `workout_sessions` | 1:N | CASCADE | Użytkownik ma wiele sesji. Usunięcie użytkownika usuwa wszystkie jego sesje. |
| `users` → `workout_sessions.deleted_by` | 1:N | SET NULL | Audit usunięć - kto usunął sesję. |
| `muscle_categories` → `exercises` | 1:N | RESTRICT | Kategoria ma wiele ćwiczeń. Nie można usunąć kategorii używanej przez ćwiczenia. |
| `exercises` → `workout_exercises` | 1:N | RESTRICT | Ćwiczenie może być w wielu sesjach. Nie można usunąć ćwiczenia używanego w sesjach. |
| `workout_sessions` → `workout_exercises` | 1:N | CASCADE | Sesja ma wiele ćwiczeń. Usunięcie sesji usuwa wszystkie jej ćwiczenia. |
| `workout_exercises` → `exercise_sets` | 1:N | CASCADE | Ćwiczenie w sesji ma wiele grup serii. Usunięcie ćwiczenia usuwa wszystkie serie. |

### Kluczowe zasady CASCADE

**Łańcuch usuwania przy DELETE użytkownika**:
```
users → workout_sessions → workout_exercises → exercise_sets
         (CASCADE)           (CASCADE)           (CASCADE)
```

**Soft delete sesji**:
- `workout_sessions.deleted_at IS NOT NULL` → sesja niewidoczna
- Fizyczne rekordy `workout_exercises` i `exercise_sets` pozostają
- Zapytania: `JOIN workout_sessions WHERE deleted_at IS NULL`

**Ochrona słowników (RESTRICT)**:
- Nie można usunąć `muscle_categories` jeśli są powiązane `exercises`
- Nie można usunąć `exercises` jeśli są używane w `workout_exercises`

---

## 3. Indeksy

### 3.1 Primary Keys (automatyczne indeksy)
- `users.id`
- `muscle_categories.id`
- `exercises.id`
- `workout_sessions.id`
- `workout_exercises.id`
- `exercise_sets.id`

### 3.2 Unique Indexes

```sql
-- Email case-insensitive (funkcjonalny indeks)
CREATE UNIQUE INDEX idx_users_email_lower ON users (LOWER(email));

-- Nazwy kategorii mięśniowych
CREATE UNIQUE INDEX idx_muscle_categories_name_pl ON muscle_categories (name_pl);
CREATE UNIQUE INDEX idx_muscle_categories_name_en ON muscle_categories (name_en);

-- Nazwy ćwiczeń (globalna unikalność)
CREATE UNIQUE INDEX idx_exercises_name ON exercises (name);
```

### 3.3 Foreign Key Indexes (wydajność JOIN)

```sql
-- exercises
CREATE INDEX idx_exercises_muscle_category_id ON exercises (muscle_category_id);

-- workout_sessions
CREATE INDEX idx_workout_sessions_user_id_date ON workout_sessions (user_id, date DESC);
CREATE INDEX idx_workout_sessions_deleted_by ON workout_sessions (deleted_by);

-- workout_exercises
CREATE INDEX idx_workout_exercises_workout_session_id ON workout_exercises (workout_session_id);
CREATE INDEX idx_workout_exercises_exercise_id ON workout_exercises (exercise_id);

-- exercise_sets
CREATE INDEX idx_exercise_sets_workout_exercise_id ON exercise_sets (workout_exercise_id);
```

### 3.4 Partial Indexes (optymalizacja zapytań)

```sql
-- Aktywne sesje (najczęstsze zapytanie - WHERE deleted_at IS NULL)
CREATE INDEX idx_workout_sessions_active ON workout_sessions (user_id, date DESC) 
WHERE deleted_at IS NULL;
```

### 3.5 Uzasadnienie indeksów

| Indeks | Zapytanie | Częstotliwość |
|--------|-----------|---------------|
| `idx_users_email_lower` | Logowanie użytkownika | Każde logowanie |
| `idx_workout_sessions_user_id_date` | Dashboard - ostatnie 5 sesji | Każde otwarcie dashboardu |
| `idx_workout_sessions_active` | Historia aktywnych sesji | Każde przeglądanie historii |
| `idx_workout_exercises_exercise_id` | Statystyki - max ciężar dla ćwiczenia | Wybór ćwiczenia w panelu statystyk |
| `idx_exercises_muscle_category_id` | Filtrowanie ćwiczeń po kategorii | Dodawanie ćwiczenia do sesji |

---

## 4. Zasady PostgreSQL (Row Level Security)

**Status**: Brak RLS w MVP

### Uzasadnienie
- Izolacja danych użytkowników implementowana w warstwie aplikacji (Symfony)
- Wszystkie zapytania filtrowane przez `user_id` w Query Builderze
- RLS nie jest wymagane dla skali MVP (~100 użytkowników)
- Uproszczenie architektury i debugowania

### Bezpieczeństwo w aplikacji
```php
// Przykład - Doctrine Query Builder z automatycznym filtrem user_id
$sessions = $this->workoutSessionRepository->createQueryBuilder('ws')
    ->where('ws.user = :user')
    ->andWhere('ws.deletedAt IS NULL')
    ->setParameter('user', $currentUser)
    ->orderBy('ws.date', 'DESC')
    ->getQuery()
    ->getResult();
```

### Przyszła implementacja RLS (poza MVP)
Jeśli potrzebna w przyszłości:

```sql
-- Włączenie RLS dla workout_sessions
ALTER TABLE workout_sessions ENABLE ROW LEVEL SECURITY;

-- Polityka: użytkownik widzi tylko swoje sesje
CREATE POLICY workout_sessions_isolation ON workout_sessions
    USING (user_id = current_setting('app.current_user_id')::CHAR(26));
```

---

## 5. Dodatkowe uwagi i decyzje projektowe

### 5.1 ULID jako klucze główne

**Format**: CHAR(26) - przykład: `01ARZ3NDEKTSV4RRFFQ69G5FAV`

**Zalety**:
- Sortowalne chronologicznie (zawierają timestamp)
- Zgodne z Symfony 7.3 best practices
- Bezpieczniejsze niż auto-increment (brak wycieków informacji o liczbie rekordów)
- Generowane w aplikacji (Doctrine entity constructor)

**Implementacja w Symfony**:
```php
use Symfony\Component\Uid\Ulid;

#[ORM\Id]
#[ORM\Column(type: 'string', length: 26)]
private string $id;

public function __construct() {
    $this->id = (string) new Ulid();
}
```

### 5.2 Ciężar w gramach (INTEGER)

**Decyzja**: `weight_grams INTEGER` zamiast `weight_kg DECIMAL`

**Zalety**:
- Eliminuje problemy z floating point arithmetic
- Dokładność do 1 grama
- Wydajniejsze obliczenia (INTEGER vs DECIMAL)
- Brak zaokrągleń i błędów precyzji

**Konwersja w aplikacji**:
```php
// Zapis: kg → gramy
$weightGrams = (int) ($weightKg * 1000);

// Odczyt: gramy → kg
$weightKg = $weightGrams / 1000;
```

**Zakres wartości**:
- 0 gram = 0 kg (ciężar własnego ciała)
- 500,000 gram = 500 kg (maksymalny limit MVP)

### 5.3 Soft Delete dla workout_sessions

**Implementacja**:
- `deleted_at TIMESTAMPTZ NULL` - data usunięcia
- `deleted_by CHAR(26) NULL` - użytkownik, który usunął (audit)

**Korzyści**:
- Możliwość przywracania sesji w przyszłości
- Pełny audit usunięć (kto, kiedy)
- Bezpieczniejsze dla użytkownika (ochrona przed przypadkowym usunięciem)

**Zapytania**:
```sql
-- Aktywne sesje
SELECT * FROM workout_sessions WHERE deleted_at IS NULL;

-- Usunięte sesje (do potencjalnego przywracania)
SELECT * FROM workout_sessions WHERE deleted_at IS NOT NULL;
```

**Powiązane rekordy**:
- `workout_exercises` i `exercise_sets` pozostają w bazie
- Niewidoczne przez JOIN: `... JOIN workout_sessions ws WHERE ws.deleted_at IS NULL`

### 5.4 Struktura exercise_sets z sets_count

**Optymalizacja zapisu**:
- Tradycyjnie: 3x6@40kg = 3 osobne rekordy
- Optymalizacja: 3x6@40kg = 1 rekord (`sets_count=3, reps=6, weight_grams=40000`)

**Zalety**:
- Redukcja liczby rekordów (~75% mniej przy typowych notacjach)
- Odzwierciedla naturalny sposób zapisu treningowego
- Łatwiejsze zapytania statystyczne (MAX weight_grams)

**Przykłady**:
```
1x8@20kg  → sets_count=1, reps=8,  weight_grams=20000
3x10@70kg → sets_count=3, reps=10, weight_grams=70000
5x5@100kg → sets_count=5, reps=5,  weight_grams=100000
```

### 5.5 Sortowanie po created_at

**Decyzja**: Brak pól `order_in_session` i `order_in_exercise`

**Uzasadnienie**:
- Naturalny porządek chronologiczny (kolejność dodawania)
- Prostszy model danych (mniej kolumn)
- Brak konieczności zarządzania kolejnością przy edycji/usuwaniu
- ULID w `created_at` zapewnia mikrosekundową precyzję

**Zapytania**:
```sql
-- Ćwiczenia w sesji (chronologicznie)
SELECT * FROM workout_exercises 
WHERE workout_session_id = ? 
ORDER BY created_at ASC;

-- Serie w ćwiczeniu (chronologicznie)
SELECT * FROM exercise_sets 
WHERE workout_exercise_id = ? 
ORDER BY created_at ASC;
```

### 5.6 Wielojęzyczność (i18n)

**Status MVP**: Polski jako domyślny język

**Architektura przyszłościowa**:
- `exercises.name` (polski) + `exercises.name_en` (angielski, NULL w MVP)
- `muscle_categories.name_pl` + `muscle_categories.name_en`

**Rozbudowa**:
- Łatwe dodanie kolejnych języków: nowe kolumny `name_de`, `name_fr`, etc.
- Alternatywa (przy >5 językach): osobna tabela `exercise_translations`

**Zapytanie z wyborem języka**:
```sql
-- Aplikacja wybiera kolumnę w zależności od języka użytkownika
SELECT 
    id,
    CASE WHEN :lang = 'en' THEN COALESCE(name_en, name) ELSE name END as exercise_name
FROM exercises;
```

### 5.7 Walidacja danych

**Strategia**: Walidacja w aplikacji (Symfony), nie w bazie danych

**Limity MVP** (walidowane w Symfony):
- Ćwiczenia na sesję: maksymalnie 15
- Serie na ćwiczenie: maksymalnie 20 (suma `sets_count`)
- Powtórzenia: 1-100
- Ciężar: 0-500 kg (0-500000 gramów)

**Uzasadnienie**:
- Przyjazne komunikaty błędów po stronie aplikacji
- Elastyczność zmian bez migracji bazy
- Zgodne z wzorcem Symfony (Symfony Validator)

**Constraints w bazie**:
- Tylko podstawowe: NOT NULL, UNIQUE, Foreign Keys
- Brak CHECK constraints dla logiki biznesowej

### 5.8 Typowe zapytania i optymalizacja

#### Dashboard - ostatnie 5 sesji użytkownika
```sql
SELECT * FROM workout_sessions
WHERE user_id = ? AND deleted_at IS NULL
ORDER BY date DESC, created_at DESC
LIMIT 5;
```
**Indeks**: `idx_workout_sessions_active` (partial index)

#### Statystyki - maksymalny ciężar dla ćwiczenia
```sql
SELECT 
    ws.date,
    MAX(es.weight_grams) as max_weight
FROM workout_sessions ws
JOIN workout_exercises we ON we.workout_session_id = ws.id
JOIN exercise_sets es ON es.workout_exercise_id = we.id
WHERE ws.user_id = ? 
  AND ws.deleted_at IS NULL
  AND we.exercise_id = ?
GROUP BY ws.id, ws.date
ORDER BY ws.date ASC;
```
**Indeksy**: `idx_workout_exercises_exercise_id`, `idx_workout_sessions_active`

#### Historia - filtr ostatnie 30 dni
```sql
SELECT * FROM workout_sessions
WHERE user_id = ? 
  AND deleted_at IS NULL
  AND date >= CURRENT_DATE - INTERVAL '30 days'
ORDER BY date DESC;
```
**Indeks**: `idx_workout_sessions_active`

### 5.9 Migracje i seeding

**Kolejność migracji**:
1. `create_users_table`
2. `create_muscle_categories_table`
3. `create_exercises_table`
4. `create_workout_sessions_table`
5. `create_workout_exercises_table`
6. `create_exercise_sets_table`
7. `create_indexes` (wszystkie indeksy)

**Seed data** (wykonać po migracjach):
1. `seed_muscle_categories` - 6 kategorii (Klatka piersiowa, Plecy, Nogi, Barki, Biceps, Triceps)
2. `seed_exercises` - 50-70 ćwiczeń (do przygotowania)

**Doctrine Migrations** (Symfony):
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load  # dla seed data
```

### 5.10 Szacunkowy rozmiar bazy (3 miesiące MVP)

**Założenia**:
- 100 aktywnych użytkowników
- 3 sesje/tydzień/użytkownik → ~1,300 sesji
- 8 ćwiczeń/sesję → ~10,400 workout_exercises
- 4 grupy serii/ćwiczenie → ~41,600 exercise_sets

**Rozmiar tabel**:
- `users`: 100 rekordów → ~20 KB
- `muscle_categories`: 6 rekordów → <1 KB
- `exercises`: 70 rekordów → ~10 KB
- `workout_sessions`: 1,300 rekordów → ~200 KB
- `workout_exercises`: 10,400 rekordów → ~1.5 MB
- `exercise_sets`: 41,600 rekordów → ~5 MB

**Total**: ~7 MB danych + ~5-10 MB indeksy = **12-17 MB**

**Skalowalność**: Struktura skaluje do milionów sesji bez zmian architektury

---

## 6. Podsumowanie decyzji architektonicznych

| Aspekt | Decyzja | Uzasadnienie |
|--------|---------|--------------|
| **Klucze główne** | ULID (CHAR(26)) | Sortowalne, bezpieczne, zgodne z Symfony |
| **Ciężar** | INTEGER (gramy) | Eliminuje floating point errors, wydajność |
| **Timestamps** | TIMESTAMPTZ | Obsługa stref czasowych, audit |
| **Soft delete** | Tylko workout_sessions | Możliwość przywracania, audit usunięć |
| **Sortowanie** | created_at | Prostszy model, naturalna kolejność |
| **Wielojęzyczność** | Kolumny name/name_en | Przygotowanie pod i18n, łatwa rozbudowa |
| **Walidacja** | Aplikacja (Symfony) | Przyjazne błędy, elastyczność |
| **Indeksy** | Funkcjonalne + partial | Optymalizacja kluczowych zapytań |
| **RLS** | Brak (aplikacja) | Uproszczenie MVP, izolacja w Symfony |
| **Serie** | Grupowanie (sets_count) | Redukcja rekordów, naturalna notacja |

---

## 7. Następne kroki (implementacja)

1. ✅ **Schemat bazy - GOTOWE** (ten dokument)
2. ⏳ **Migracje Doctrine** - utworzenie tabel w PostgreSQL
3. ⏳ **Seed data** - muscle_categories (6 rekordów)
4. ⏳ **Seed data** - exercises (50-70 rekordów, do przygotowania listy)
5. ⏳ **Encje Symfony** - mapowanie ORM (User, WorkoutSession, etc.)
6. ⏳ **Repozytoria** - implementacja zapytań (findByUser, getMaxWeight, etc.)
7. ⏳ **Testy** - Unit testy dla encji, integracyjne dla repozytoriów

---

**Wersja dokumentu**: 1.0  
**Data utworzenia**: 2025-10-10  
**Stack**: PostgreSQL + Symfony 7.3 (PHP 8.4) + Doctrine ORM  
**Projekt**: WorkoutTracker MVP

