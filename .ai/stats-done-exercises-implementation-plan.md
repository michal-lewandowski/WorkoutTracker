# Plan Implementacji: Zmiana listy ćwiczeń w panelu statystyk

**Data utworzenia:** 2026-01-04  
**Status:** Do implementacji  
**Priorytet:** Medium  
**Szacowany czas:** 1-2 godziny

---

## 📋 Spis treści

1. [Kontekst i cel zmiany](#kontekst-i-cel-zmiany)
2. [Analiza obecnego stanu](#analiza-obecnego-stanu)
3. [Porównanie API endpoints](#porównanie-api-endpoints)
4. [Decyzje techniczne](#decyzje-techniczne)
5. [Plan implementacji krok po kroku](#plan-implementacji-krok-po-kroku)
6. [Struktura plików](#struktura-plików)
7. [Testy i weryfikacja](#testy-i-weryfikacja)
8. [Potencjalne problemy](#potencjalne-problemy)
9. [Checklist](#checklist)

---

## 🎯 Kontekst i cel zmiany

### Obecna sytuacja
Komponent `StatsPanel.tsx` wyświetla listę **wszystkich ćwiczeń ze słownika** (endpoint `/exercises`), co oznacza, że użytkownik widzi również ćwiczenia, których nigdy nie wykonał.

### Cel zmiany
Zmienić implementację tak, aby w panelu statystyk wyświetlały się **tylko te ćwiczenia, które użytkownik rzeczywiście wykonał** przynajmniej raz (endpoint `/statistics/done-exercises`).

### Korzyści
- ✅ Lepsza UX - użytkownik widzi tylko relevantne ćwiczenia
- ✅ Szybsze znajdowanie swojich ćwiczeń
- ✅ Dodatkowa informacja - liczba wykonanych treningów z danym ćwiczeniem (`workoutsCount`)
- ✅ Mniejsze obciążenie UI - krótsza lista w select

---

## 🔍 Analiza obecnego stanu

### Aktualny przepływ danych w StatsPanel.tsx

```
┌─────────────────────────────────────────────────────────────┐
│                      StatsPanel.tsx                         │
│                                                              │
│  useExercises() ────> /exercises endpoint                   │
│       │                                                      │
│       ├─> exercises: Exercise[]                            │
│       ├─> isLoading: boolean                               │
│       └─> error: Error | undefined                         │
│                                                              │
│  useExerciseStatistics(selectedExerciseId)                 │
│       │                                                      │
│       └─> statistics: ExerciseStatistics | undefined       │
│                                                              │
│  <select> wyświetla WSZYSTKIE ćwiczenia ze słownika        │
└─────────────────────────────────────────────────────────────┘
```

### Pliki zaangażowane obecnie

1. **`StatsPanel.tsx`** (linia 20)
   - Używa `useExercises()` do pobierania listy
   - Wyświetla dropdown z wszystkimi ćwiczeniami

2. **`useExercises.ts`**
   - Hook używający SWR + localStorage cache (24h)
   - Endpoint: `/exercises`
   - Zwraca: `Exercise[]`

3. **`types.ts`**
   - Typ `Exercise` (bez `workoutsCount`)

---

## 🔄 Porównanie API endpoints

### Endpoint: `/exercises`

**Swagger path:** `/exercises`  
**Metoda:** GET  
**Autoryzacja:** Bearer JWT  
**Parametry:**
- `muscleCategoryId` (optional) - filtrowanie po kategorii
- `search` (optional) - wyszukiwanie po nazwie
- `lang` (optional) - język (pl/en)

**Response:** `Exercise[]`

```typescript
interface Exercise {
  id: string;                      // UUID4
  name: string;                    // Polska nazwa
  nameEn: string | null;           // Angielska nazwa
  muscleCategoryId: string;        // UUID4
  muscleCategory: MuscleCategory;  // Pełny obiekt kategorii
  createdAt: string;               // ISO 8601
}
```

**Przykład:**
```json
[
  {
    "id": "a13ed185-ef5e-424f-abb9-5a3f2b1d87cd",
    "name": "Wyciskanie sztangi leżąc",
    "nameEn": "Barbell Bench Press",
    "muscleCategoryId": "13802679-0aa1-4d24-bc96-461b06af0f23",
    "muscleCategory": {
      "id": "13802679-0aa1-4d24-bc96-461b06af0f23",
      "namePl": "Klatka piersiowa",
      "nameEn": "Chest",
      "createdAt": "2025-12-30T23:16:19+00:00"
    },
    "createdAt": "2025-12-30T23:16:19+00:00"
  }
]
```

---

### Endpoint: `/statistics/done-exercises`

**Swagger path:** `/statistics/done-exercises`  
**Metoda:** GET  
**Autoryzacja:** Bearer JWT  
**Parametry:**
- `dateFrom` (optional) - filtrowanie od daty (YYYY-MM-DD)
- `dateTo` (optional) - filtrowanie do daty (YYYY-MM-DD)
- `sortBy` (optional) - sortowanie: `name` | `createdAt` | `lastUsed` (default: `name`)
- `sortOrder` (optional) - kierunek: `asc` | `desc` (default: `asc`)

**Response:** `DoneExercise[]`

```typescript
interface DoneExercise {
  id: string;                      // UUID4
  name: string;                    // Polska nazwa
  workoutsCount: number;           // ⭐ NOWE POLE - liczba treningów
  muscleCategory: MuscleCategory;  // Pełny obiekt kategorii
  createdAt: string;               // ISO 8601
  updatedAt: string;               // ISO 8601
}
```

**Przykład:**
```json
[
  {
    "id": "a13ed185-ef5e-424f-abb9-5a3f2b1d87cd",
    "name": "Diamentowe pompki",
    "workoutsCount": 2,
    "muscleCategory": {
      "id": "dffba9c1-5788-4f85-bd4b-be347269877f",
      "namePl": "Triceps",
      "nameEn": "Triceps",
      "createdAt": "2025-12-30T23:16:19+00:00"
    },
    "createdAt": "2025-12-30T23:16:19+00:00",
    "updatedAt": "2025-12-30T23:16:19+00:00"
  },
  {
    "id": "f4311d90-d67b-4968-a14f-cc5f5589e69d",
    "name": "Dipy na barki",
    "workoutsCount": 2,
    "muscleCategory": {
      "id": "13802679-0aa1-4d24-bc96-461b06af0f23",
      "namePl": "Klatka piersiowa",
      "nameEn": "Chest",
      "createdAt": "2025-12-30T23:16:19+00:00"
    },
    "createdAt": "2025-12-30T23:16:19+00:00",
    "updatedAt": "2025-12-30T23:16:19+00:00"
  }
]
```

---

### Kluczowe różnice

| Aspekt | `/exercises` | `/statistics/done-exercises` |
|--------|--------------|------------------------------|
| **Zakres danych** | Wszystkie ćwiczenia ze słownika | Tylko wykonane przez użytkownika |
| **Pole `nameEn`** | ✅ Obecne (nullable) | ❌ Brak |
| **Pole `muscleCategoryId`** | ✅ Obecne | ❌ Brak (tylko zagnieżdżony obiekt) |
| **Pole `workoutsCount`** | ❌ Brak | ✅ Obecne |
| **Pole `updatedAt`** | ❌ Brak | ✅ Obecne |
| **Filtrowanie** | Po kategorii, wyszukiwanie | Po dacie, sortowanie |
| **Zmienność danych** | Rzadka (słownik) | Częsta (po każdym treningu) |
| **Cache w localStorage** | Tak (24h) | ❓ Do rozważenia |

---

## 💡 Decyzje techniczne

### 1. Czy używać cache localStorage dla done-exercises?

**Opcja A: Brak cache (REKOMENDOWANE)**
- ✅ Zawsze aktualne dane po dodaniu treningu
- ✅ Prostsza implementacja
- ✅ Dane zmieniają się częściej niż słownik exercises
- ✅ Endpoint jest szybki (zwraca tylko wykonane ćwiczenia)
- ❌ Dodatkowe zapytanie przy każdym odświeżeniu

**Opcja B: Cache z krótszym TTL (np. 5 minut)**
- ✅ Mniej zapytań do API
- ❌ Ryzyko nieaktualnych danych po dodaniu treningu
- ❌ Konieczność invalidacji cache po dodaniu workout exercise

**Decyzja:** Opcja A - bez cache lub z bardzo krótkim TTL (5 min) + invalidacja po dodaniu ćwiczenia

---

### 2. Czy tworzyć nowy hook czy modyfikować istniejący?

**Opcja A: Nowy hook `useDoneExercises()` (REKOMENDOWANE)**
- ✅ Separation of concerns
- ✅ Hook `useExercises()` nadal dostępny dla innych części aplikacji
- ✅ Łatwiejsze utrzymanie
- ✅ Jasna semantyka nazwy

**Opcja B: Modyfikacja `useExercises()` z parametrem**
- ❌ Większa złożoność jednego hooka
- ❌ Może zmylić developerów

**Decyzja:** Opcja A - nowy hook `useDoneExercises()`

---

### 3. Jak obsłużyć różnice w typach?

**Podejście:**
- Dodać nowy typ `DoneExercise` do `types.ts`
- Nie modyfikować istniejącego typu `Exercise`
- W komponencie StatsPanel używać `DoneExercise[]`

---

### 4. Czy obsługiwać parametry sortowania/filtrowania?

**Decyzja:**
- **Dla MVP:** Używać domyślnych wartości (sortowanie alfabetyczne)
- **Przyszłość:** Dodać opcjonalne parametry do hooka (dateFrom, dateTo, sortBy, sortOrder)

---

## 📝 Plan implementacji krok po kroku

### Krok 1: Dodanie typu `DoneExercise` do types.ts

**Plik:** `frontend/src/lib/types.ts`

**Działanie:**
Dodać nowy interface poniżej typu `Exercise` w sekcji "Muscle Categories & Exercises".

```typescript
export interface DoneExercise {
  id: string;
  name: string;
  workoutsCount: number;
  muscleCategory: MuscleCategory;
  createdAt: string;
  updatedAt: string;
}
```

**Uwaga:** Typ jest kompatybilny z response z `/statistics/done-exercises` zgodnie ze Swaggerem (linie 1251-1288).

---

### Krok 2: Stworzenie hooka `useDoneExercises`

**Plik:** `frontend/src/hooks/useDoneExercises.ts` (NOWY PLIK)

**Działanie:**
Stworzyć nowy hook używający SWR do pobierania wykonanych ćwiczeń.

**Implementacja:**

```typescript
// ============================================
// useDoneExercises Hook
// Fetch exercises performed by user
// ============================================

'use client';

import useSWR from 'swr';
import { DoneExercise } from '@/lib/types';
import { swrFetcher } from '@/lib/api';

// ============================================
// Query Parameters Interface
// ============================================

interface DoneExercisesParams {
  dateFrom?: string;
  dateTo?: string;
  sortBy?: 'name' | 'createdAt' | 'lastUsed';
  sortOrder?: 'asc' | 'desc';
}

// ============================================
// Hook
// ============================================

/**
 * Hook to fetch exercises performed by user
 * 
 * Features:
 * - Fetches only exercises that user has performed at least once
 * - Includes workoutsCount for each exercise
 * - Supports date filtering and sorting
 * - No localStorage cache (data changes frequently)
 * 
 * @param params - Optional query parameters
 * @returns done exercises array, loading state, and error
 */
export function useDoneExercises(params?: DoneExercisesParams) {
  // Build query string
  const queryString = params
    ? `?${new URLSearchParams(params as Record<string, string>).toString()}`
    : '';

  const { data, error, isLoading, mutate } = useSWR<DoneExercise[]>(
    `/statistics/done-exercises${queryString}`,
    (url: string) => swrFetcher<DoneExercise[]>(url),
    {
      revalidateOnFocus: false,
      revalidateOnReconnect: false,
      dedupingInterval: 60000, // 1 minute - dane zmieniają się rzadziej niż potrzeba refetch
    }
  );

  return {
    exercises: data ?? [],
    isLoading,
    error,
    mutate, // For manual cache invalidation (np. po dodaniu workout exercise)
  };
}
```

**Parametry SWR:**
- `revalidateOnFocus: false` - nie refetch przy powrocie do zakładki (oszczędność zapytań)
- `revalidateOnReconnect: false` - nie refetch przy powrocie połączenia
- `dedupingInterval: 60000` - deduplicja przez 1 minutę (rozsądny balans)

**Brak localStorage cache:**
- Dane zmieniają się po każdym dodaniu ćwiczenia do treningu
- Endpoint jest szybki (tylko wykonane ćwiczenia, nie cały słownik)
- Unikamy problemów z synchronizacją

---

### Krok 3: Aktualizacja komponentu StatsPanel.tsx

**Plik:** `frontend/src/components/charts/StatsPanel.tsx`

**Działanie:**
Zamienić `useExercises()` na `useDoneExercises()`.

**Zmiany:**

```typescript
// PRZED:
import { useExercises } from '@/hooks/useExercises';

export function StatsPanel() {
  const { exercises, isLoading: exercisesLoading } = useExercises();
  // ...
}
```

```typescript
// PO:
import { useDoneExercises } from '@/hooks/useDoneExercises';

export function StatsPanel() {
  const { exercises, isLoading: exercisesLoading } = useDoneExercises();
  // ...
}
```

**Szczegółowy diff:**

```diff
// ============================================
// Stats Panel Component
// Exercise selector + progress chart
// ============================================

'use client';

import { useState } from 'react';
- import { useExercises } from '@/hooks/useExercises';
+ import { useDoneExercises } from '@/hooks/useDoneExercises';
import { useExerciseStatistics } from '@/hooks/useExerciseStatistics';
import { Card, CardContent } from '@/components/ui/Card';
import { Spinner } from '@/components/ui/Spinner';
import { ExerciseProgressChart } from './ExerciseProgressChart';

// ============================================
// Component
// ============================================

export function StatsPanel() {
-  const { exercises, isLoading: exercisesLoading } = useExercises();
+  const { exercises, isLoading: exercisesLoading } = useDoneExercises();
  const [selectedExerciseId, setSelectedExerciseId] = useState<string | null>(
    null
  );

  const { statistics, isLoading: statsLoading } = useExerciseStatistics(
    selectedExerciseId
  );

  // ... reszta bez zmian
}
```

**Uwagi:**
- Interfejs hooka jest identyczny - zwraca `exercises`, `isLoading`, `error`
- Jedyna zmiana to nazwa importowanego hooka
- Logika komponentu pozostaje bez zmian
- Dropdown automatycznie wyświetli tylko wykonane ćwiczenia

---

### Krok 4: (Opcjonalnie) Wyświetlanie workoutsCount w dropdown

**Plik:** `frontend/src/components/charts/StatsPanel.tsx`

**Działanie:**
Możemy wzbogacić dropdown o wyświetlanie liczby wykonanych treningów.

**Przykład:**

```typescript
// OPCJONALNE ULEPSZENIE
<select
  id="exercise-select"
  value={selectedExerciseId || ''}
  onChange={(e) => handleExerciseSelect(e.target.value)}
  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
>
  <option value="">-- Wybierz ćwiczenie --</option>
  {exercises.map((exercise) => (
    <option key={exercise.id} value={exercise.id}>
      {exercise.name} ({exercise.workoutsCount} {exercise.workoutsCount === 1 ? 'trening' : 'treningi'})
    </option>
  ))}
</select>
```

**Przykładowy wynik:**
```
-- Wybierz ćwiczenie --
Diamentowe pompki (2 treningi)
Dipy na barki (2 treningi)
Wyciskanie sztangi (5 treningów)
```

**Decyzja:** Można dodać w późniejszym etapie, nie jest krytyczne dla MVP.

---

### Krok 5: Dodanie hooka do eksportu (jeśli potrzeba)

**Plik:** `frontend/src/hooks/index.ts` (jeśli istnieje)

Jeśli projekt używa pliku index.ts do eksportów, dodać:

```typescript
export { useDoneExercises } from './useDoneExercises';
```

---

## 📁 Struktura plików

### Pliki do modyfikacji

```
frontend/src/
├── lib/
│   └── types.ts                          [MODYFIKACJA] +8 linii
├── hooks/
│   └── useDoneExercises.ts              [NOWY PLIK] ~70 linii
└── components/
    └── charts/
        └── StatsPanel.tsx                [MODYFIKACJA] 2 linie zmiany
```

### Pliki bez zmian (ale powiązane)

```
frontend/src/
├── hooks/
│   ├── useExercises.ts                  [BEZ ZMIAN] - nadal używany w innych miejscach
│   └── useExerciseStatistics.ts         [BEZ ZMIAN]
├── lib/
│   └── api.ts                            [BEZ ZMIAN]
└── components/
    └── charts/
        └── ExerciseProgressChart.tsx     [BEZ ZMIAN]
```

---

## ✅ Testy i weryfikacja

### 1. Testy jednostkowe (opcjonalne dla MVP)

**Hook useDoneExercises:**
- Test pobierania danych z API
- Test obsługi błędów
- Test parametrów query

**Przykład (jeśli używamy Jest + React Testing Library):**

```typescript
import { renderHook, waitFor } from '@testing-library/react';
import { useDoneExercises } from './useDoneExercises';

describe('useDoneExercises', () => {
  it('should fetch done exercises', async () => {
    const { result } = renderHook(() => useDoneExercises());
    
    await waitFor(() => {
      expect(result.current.isLoading).toBe(false);
    });
    
    expect(result.current.exercises).toBeDefined();
    expect(Array.isArray(result.current.exercises)).toBe(true);
  });
});
```

---

### 2. Testy manualne (WYMAGANE)

#### Test Case 1: Użytkownik bez wykonanych ćwiczeń

**Kroki:**
1. Zaloguj się jako nowy użytkownik (bez treningów)
2. Przejdź do dashboardu
3. Sprawdź panel statystyk

**Oczekiwany rezultat:**
- Dropdown wyświetla tylko "-- Wybierz ćwiczenie --"
- Lista jest pusta
- Brak błędów w konsoli

---

#### Test Case 2: Użytkownik z wykonanymi ćwiczeniami

**Kroki:**
1. Zaloguj się jako użytkownik z treningami
2. Przejdź do dashboardu
3. Otwórz dropdown w panelu statystyk

**Oczekiwany rezultat:**
- Lista zawiera TYLKO ćwiczenia, które użytkownik wykonał
- Kolejność alfabetyczna (domyślna)
- Po wybraniu ćwiczenia wyświetla się wykres

---

#### Test Case 3: Dodanie nowego ćwiczenia do treningu

**Kroki:**
1. Zaloguj się jako użytkownik
2. Otwórz panel statystyk - zapamiętaj listę ćwiczeń
3. Dodaj nowy trening z nowym ćwiczeniem (którego wcześniej nie wykonywałeś)
4. Wróć do dashboardu
5. Odśwież stronę (lub poczekaj 1 minutę)

**Oczekiwany rezultat:**
- Nowe ćwiczenie pojawia się w dropdownie
- `workoutsCount = 1` dla nowego ćwiczenia

---

#### Test Case 4: Stan ładowania

**Kroki:**
1. Otwórz narzędzia deweloperskie (Network tab)
2. Symuluj wolne połączenie (Throttling: Slow 3G)
3. Odśwież dashboard

**Oczekiwany rezultat:**
- Wyświetla się spinner podczas ładowania listy ćwiczeń
- Po załadowaniu spinner znika i pojawia się dropdown

---

#### Test Case 5: Obsługa błędów

**Kroki:**
1. Wyloguj się w jednej karcie
2. Spróbuj użyć dropdownu w drugiej karcie (z wygasłym tokenem)

**Oczekiwany rezultat:**
- Wyświetla się komunikat błędu lub użytkownik jest przekierowany na login
- Brak crashu aplikacji

---

### 3. Testy E2E (opcjonalne)

Jeśli projekt używa Playwright/Cypress:

```typescript
test('should display only done exercises in stats panel', async ({ page }) => {
  await page.goto('/dashboard');
  
  const select = page.locator('#exercise-select');
  const options = await select.locator('option').count();
  
  // Sprawdź, że lista nie jest pusta (zakładając że użytkownik ma treningi)
  expect(options).toBeGreaterThan(1); // +1 dla "-- Wybierz ćwiczenie --"
});
```

---

## ⚠️ Potencjalne problemy

### Problem 1: Użytkownik bez treningów

**Sytuacja:** Nowy użytkownik nie ma jeszcze żadnych treningów.

**Skutek:**
- Endpoint `/statistics/done-exercises` zwraca pustą tablicę `[]`
- Dropdown pokazuje tylko "-- Wybierz ćwiczenie --"

**Rozwiązanie:**
- To zachowanie jest poprawne i oczekiwane
- Ewentualnie: dodać informację "Nie wykonałeś jeszcze żadnego ćwiczenia. Dodaj pierwszy trening!"

---

### Problem 2: Synchronizacja po dodaniu treningu

**Sytuacja:** Użytkownik dodaje trening z nowym ćwiczeniem i wraca do dashboardu.

**Skutek:**
- Nowe ćwiczenie może nie pojawić się od razu w dropdownie (SWR cache)

**Rozwiązanie:**
- Opcja A: Refetch automatyczny po powrocie do dashboardu (ustawić `revalidateOnFocus: true`)
- Opcja B: Invalidacja cache po dodaniu treningu (wywołać `mutate()`)
- Opcja C: Odświeżenie strony przez użytkownika

**Rekomendacja:** Dodać invalidację cache w komponencie dodawania treningu:

```typescript
// W komponencie WorkoutForm (po dodaniu treningu)
import { mutate } from 'swr';

const handleSubmit = async () => {
  // ... dodaj trening
  await apiClient.post('/workout-sessions', data);
  
  // Invaliduj cache done-exercises
  mutate('/statistics/done-exercises');
  
  router.push('/dashboard');
};
```

---

### Problem 3: Wydajność z dużą liczbą ćwiczeń

**Sytuacja:** Użytkownik ma 100+ różnych ćwiczeń w historii.

**Skutek:**
- Długa lista w dropdownie
- Trudne znalezienie konkretnego ćwiczenia

**Rozwiązanie (przyszłość):**
- Dodać wyszukiwanie w dropdownie (np. react-select)
- Dodać filtrowanie po kategorii mięśniowej
- Sortowanie po `lastUsed` zamiast alfabetycznie

---

### Problem 4: Brak danych po wybraniu ćwiczenia

**Sytuacja:** Użytkownik wybiera ćwiczenie, ale endpoint `/statistics/exercise/{id}` zwraca brak danych.

**Skutek:**
- Pusty wykres

**Rozwiązanie:**
- To powinno być niemożliwe (endpoint done-exercises zwraca tylko wykonane ćwiczenia)
- Jeśli wystąpi: dodać lepszą obsługę błędów w `ExerciseProgressChart`

---

## 📋 Checklist

### Implementacja

- [ ] **Krok 1:** Dodać typ `DoneExercise` do `types.ts`
- [ ] **Krok 2:** Stworzyć hook `useDoneExercises.ts`
- [ ] **Krok 3:** Zmienić import w `StatsPanel.tsx`
- [ ] **Krok 4:** (Opcjonalnie) Dodać wyświetlanie `workoutsCount` w dropdown
- [ ] **Krok 5:** Dodać invalidację cache po dodaniu treningu (w WorkoutForm)

### Testy

- [ ] **Test 1:** Użytkownik bez treningów - pusta lista
- [ ] **Test 2:** Użytkownik z treningami - tylko wykonane ćwiczenia
- [ ] **Test 3:** Dodanie nowego ćwiczenia - pojawia się w liście
- [ ] **Test 4:** Stan ładowania - spinner działa poprawnie
- [ ] **Test 5:** Obsługa błędów - brak crashu

### Dokumentacja

- [ ] Zaktualizować komentarze w kodzie
- [ ] Dodać JSDoc do nowego hooka
- [ ] Zaktualizować README (jeśli potrzeba)

### Code Review

- [ ] Sprawdzić TypeScript - brak błędów kompilacji
- [ ] Sprawdzić linter - kod zgodny z regułami projektu
- [ ] Sprawdzić importy - brak nieużywanych
- [ ] Sprawdzić konsole.log - usunąć debug logi

---

## 🚀 Po implementacji

### Kolejne kroki (Nice to Have)

1. **Wyświetlanie workoutsCount w dropdown**
   - Dodać informację "(X treningów)" przy każdym ćwiczeniu

2. **Sortowanie po ostatnim użyciu**
   - Dodać przycisk "Sortuj po ostatnim użyciu"
   - Parametr `sortBy=lastUsed` w hooku

3. **Filtrowanie po dacie**
   - Dodać date picker "Pokaż ćwiczenia z ostatnich X miesięcy"
   - Parametry `dateFrom` i `dateTo` w hooku

4. **Wyszukiwanie w dropdownie**
   - Zastąpić `<select>` przez komponent z wyszukiwaniem (np. Combobox z shadcn/ui)

5. **Optymalizacja wydajności**
   - Jeśli lista jest bardzo długa: dodać paginację lub virtualizację

---

## 📚 Źródła i referencje

- **Swagger API:** `/docs/swagger.json` - linie 977-1070 (endpoint done-exercises)
- **Typ DoneExercise:** `/docs/swagger.json` - linie 1251-1288
- **Komponent StatsPanel:** `/frontend/src/components/charts/StatsPanel.tsx`
- **Hook useExercises:** `/frontend/src/hooks/useExercises.ts` (referencja dla nowego hooka)
- **SWR dokumentacja:** https://swr.vercel.app/

---

## 🎯 Sukces implementacji

Implementacja zostanie uznana za udaną, gdy:

✅ Dropdown w panelu statystyk pokazuje **TYLKO** ćwiczenia wykonane przez użytkownika  
✅ Nowy użytkownik widzi pustą listę (bez błędów)  
✅ Po dodaniu nowego ćwiczenia do treningu, pojawia się ono w liście (po odświeżeniu)  
✅ Wykres nadal działa poprawnie po wybraniu ćwiczenia  
✅ Brak błędów TypeScript  
✅ Brak błędów w konsoli przeglądarki  
✅ Kod jest zgodny z regułami projektu (lint pass)  

---

**Autor planu:** AI Assistant (Senior Frontend Developer)  
**Review:** Do wykonania przez tech leada  
**Szacowany czas implementacji:** 1-2 godziny  

---

## 📝 Notatki do implementacji

- Hook `useExercises()` **nie jest usuwany** - jest nadal używany w innych miejscach aplikacji (np. przy dodawaniu ćwiczeń do treningu)
- Nowy hook `useDoneExercises()` jest dedykowany **tylko dla panelu statystyk**
- Brak potrzeby migracji danych - to tylko zmiana źródła danych na frontendzie
- Backend endpoint `/statistics/done-exercises` jest już zaimplementowany i przetestowany (zgodnie ze Swaggerem)


