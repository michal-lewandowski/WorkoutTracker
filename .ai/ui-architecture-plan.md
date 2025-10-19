# Architektura UI dla WorkoutTracker MVP

## Spis treści
1. [Decyzje architektoniczne](#decyzje-architektoniczne)
2. [Rekomendacje](#rekomendacje)
3. [Szczegółowe planowanie architektury UI](#szczegółowe-planowanie-architektury-ui)
4. [Nierozwiązane kwestie](#nierozwiązane-kwestie)

---

## Decyzje architektoniczne

### 1. Formularz sesji treningowej
**Decyzja**: Pojedynczy ekran z dynamicznym dodawaniem ćwiczeń i serii, z możliwością zapisywania postępu na bieżąco poprzez osobne requesty API.

### 2. Zarządzanie stanem JWT
**Decyzja**: Token przechowywany w localStorage + React Context API + client-side route guards (podejście uproszczone dla MVP).

### 3. Renderowanie Dashboard
**Decyzja**: Hybrid approach - Dashboard jako Server Component dla struktury i ostatnich 5 sesji, panel statystyk jako Client Component dla interaktywności z wykresem.

### 4. Nawigacja edycja/przeglądanie
**Decyzja**: Dedykowane widoki `/dashboard/sessions/[id]` (read-only) i `/dashboard/sessions/[id]/edit` (edycja) z osobnymi requestami dla metadanych i ćwiczeń, inline edycja serii z debounce 500ms.

### 5. Tryb offline
**Decyzja**: Brak pełnego offline-first - tylko detekcja braku połączenia z komunikatami + Local Storage dla draft'ów niezapisanych sesji (ochrona przed utratą danych).

### 6. Wyszukiwanie ćwiczeń
**Decyzja**: Client-side filtrowanie po pobraniu całego słownika (50-70 ćwiczeń) przy pierwszym wejściu, cache w localStorage z odświeżaniem co 24h.

### 7. Walidacja formularzy
**Decyzja**: Hybrid validation - hasło real-time (onChange) z wizualnymi wskaźnikami, pozostałe pola onBlur, wykorzystanie react-hook-form z zod schema.

### 8. Struktura komponentów formularzy
**Decyzja**: React-hook-form z useFieldArray dla dynamicznych serii, FormProvider + useFormContext dla uniknięcia prop drilling.

### 9. Biblioteka wykresów
**Decyzja**: Recharts dla wykresu liniowego postępu (responsive, łatwe tooltips, mobile touch support).

### 10. Paginacja historii
**Decyzja**: Infinite scroll dla mobile z Intersection Observer API, initial load 20 sesji, wykorzystanie SWR/React Query z infinite queries, loading skeletons.

---

## Rekomendacje

### 1. Architektura formularza sesji treningowej
Implementacja pojedynczego ekranu z dynamicznym dodawaniem elementów, wykorzystująca:
- POST `/workout-sessions` dla utworzenia sesji → otrzymanie sessionId
- POST `/workout-exercises` z opcjonalnym zagnieżdżonym array `sets[]` dla jednoczesnego dodania ćwiczeń z seriami

### 2. Bezpieczeństwo autentykacji
- localStorage dla tokenu JWT w połączeniu z React Context API dla stanu użytkownika
- `middleware.ts` w Next.js 14 dla weryfikacji i przekierowań na chronionych trasach
- Bearer token w Authorization header dla wszystkich API requests

### 3. Optymalizacja renderowania
- Server Components dla statycznych danych (lista sesji)
- Client Components (`'use client'`) dla interaktywnych elementów (wykresy, formularze)
- Cache'owanie z React Query/SWR dla optymalnej wydajności

### 4. Separacja widoków edycji
Osobne trasy dla trybu przeglądania i edycji:
- PUT `/workout-sessions/{id}` dla metadanych (date, name, notes)
- PUT `/workout-exercises/{id}` dla aktualizacji serii
- DELETE `/workout-exercises/{id}` dla usuwania ćwiczeń
- Debounced auto-save (500ms) dla lepszego UX

### 5. Strategia offline
Minimalistyczne podejście dla MVP:
- Detekcja połączenia (`navigator.onLine`)
- localStorage dla draft'ów niezapisanych sesji
- Brak Service Workers czy sync queues w MVP

### 6. Optymalizacja wyszukiwania
- Client-side filtering dla instant feedback
- Cache całego słownika w pamięci aplikacji i localStorage
- Eliminacja zbędnych API calls dla wyszukiwania
- GET `/exercises` - pojedyncze pobranie przy inicjalizacji

### 7. UX walidacji
- Real-time feedback dla hasła z wizualnymi wskaźnikami (US-004)
- onBlur validation dla pozostałych pól
- Integracja z react-hook-form i zod dla spójności z backend validation
- Field-specific error messages z API (400 validation errors)

### 8. Zarządzanie stanem formularzy
- React-hook-form jako single source of truth
- `useFieldArray` dla dynamicznych list serii
- `FormProvider` + `useFormContext` dla głęboko zagnieżdżonych komponentów
- Unikanie prop drilling

### 9. Wizualizacja danych
- Wykorzystanie biblioteki Recharts zamiast custom SVG
- Integracja z API endpoint GET `/statistics/exercise/{exerciseId}`
- API zwraca gotową strukturę `dataPoints[]` idealną dla charting libraries
- Responsive charts z native mobile touch support

### 10. Wydajność list
- Infinite scroll z Intersection Observer dla mobile-first approach
- Chunked loading (`limit`/`offset` query params)
- Cache management przez SWR/React Query
- Loading skeletons dla lepszego UX zamiast spinnerów

---

## Szczegółowe planowanie architektury UI

### Główne wymagania architektury UI

#### Mobile-First Responsive Design
- **Główna platforma**: Urządzenia mobilne
- **Nawigacja**: Bottom bar navigation dla ekranów mobile (Dashboard, Historia, Profil)
- **Responsywność**: Layouty dostosowujące się do różnych rozdzielczości (min. 320px)
- **Interakcja**: Touch-friendly kontrolki i formularze (min 44x44px dla przycisków)

#### Stack technologiczny frontend
- **Framework**: Next.js 14 App Router (hybrid SSR/CSR)
- **Język**: TypeScript dla type safety
- **Stylizacja**: Tailwind CSS
- **Formularze**: React Hook Form + Zod
- **Wykresy**: Recharts
- **Zarządzanie stanem serwera**: SWR lub React Query

---

### Kluczowe widoki, ekrany i przepływy użytkownika

#### 1. Autentykacja (niechronione routes)

##### `/login` - Strona logowania
**Komponenty**:
- `LoginForm.tsx` (Client Component)
  - Input: email (type="email")
  - Input: password (type="password")
  - Button: "Zaloguj się"
  - Link: "Nie masz konta? Zarejestruj się"

**API Integration**:
- POST `/auth/login`
- Request: `{ username: string, password: string }`
- Response: `{ user: User, token: string }`
- Success: Save token → AuthContext update → Redirect to `/dashboard`
- Error: Display error message (401: "Nieprawidłowe dane logowania")

##### `/register` - Strona rejestracji
**Komponenty**:
- `RegisterForm.tsx` (Client Component)
  - Input: email (type="email")
  - Input: password (type="password") - real-time validation
  - Input: passwordConfirmation (type="password")
  - Password strength indicator (visual checkmarks)
  - Button: "Zarejestruj się"
  - Link: "Masz już konto? Zaloguj się"

**Walidacja (Zod schema)**:
```typescript
const registerSchema = z.object({
  email: z.string().email('Nieprawidłowy format email'),
  password: z.string()
    .min(8, 'Hasło musi mieć minimum 8 znaków')
    .regex(/[A-Z]/, 'Hasło musi zawierać wielką literę')
    .regex(/\d/, 'Hasło musi zawierać cyfrę'),
  passwordConfirmation: z.string()
}).refine(data => data.password === data.passwordConfirmation, {
  message: "Hasła muszą być identyczne",
  path: ["passwordConfirmation"]
});
```

**API Integration**:
- POST `/auth/register`
- Request: `{ email: string, password: string, passwordConfirmation: string }`
- Response: `{ user: User, token: string }`
- Success: Save token → AuthContext update → Redirect to `/dashboard`
- Error 400: Display field-specific validation errors

---

#### 2. Dashboard (chronione, route: `/dashboard`)

##### Layout
**Komponenty**:
- `DashboardLayout.tsx` (Server Component)
- `BottomNav.tsx` (Client Component) - sticky bottom navigation
- `Header.tsx` - Top bar z logo i user menu

##### Dashboard Content
**Server Component** (`/dashboard/page.tsx`):
- Fetch initial data (ostatnie 5 sesji)
- Render layout structure

**Client Components**:
1. **NewSessionButton** (prominent CTA)
   - Large button "Nowa sesja"
   - Redirect to `/dashboard/sessions/new`

2. **StatsPanel** (Client Component)
   - `ExerciseSelector` - dropdown z client-side filtering
   - `ExerciseProgressChart` (Recharts LineChart)
   - Loading state podczas fetchowania danych
   - Empty state: "Wybierz ćwiczenie aby zobaczyć postęp"
   - No data state: "Nie wykonano jeszcze tego ćwiczenia"

3. **RecentSessionsList** (SSR)
   - Lista ostatnich 5 sesji
   - Każdy item: `SessionCard.tsx`
     - Data sesji
     - Nazwa (jeśli podana)
     - Liczba ćwiczeń
     - Click → redirect to `/dashboard/sessions/[id]`
   - Empty state: "Nie masz jeszcze żadnych sesji. Zacznij od dodania pierwszej!"

**API Integration**:
- GET `/workout-sessions?limit=5&offset=0&sortBy=date&sortOrder=desc` (SSR)
- GET `/exercises` (cache w localStorage, lazy load)
- GET `/statistics/exercise/{exerciseId}` (on demand po wyborze ćwiczenia)

**Data Flow**:
```typescript
// Server Component
async function DashboardPage() {
  const recentSessions = await fetch('/workout-sessions?limit=5');
  return <DashboardContent sessions={recentSessions} />;
}

// Client Component
function StatsPanel() {
  const { exercises } = useExercises(); // SWR hook
  const [selectedExerciseId, setSelectedExerciseId] = useState(null);
  const { data, isLoading } = useSWR(
    selectedExerciseId ? `/statistics/exercise/${selectedExerciseId}` : null
  );
  
  return (
    <div>
      <ExerciseSelector 
        exercises={exercises} 
        onSelect={setSelectedExerciseId} 
      />
      {isLoading && <ChartSkeleton />}
      {data && <ExerciseProgressChart data={data.dataPoints} />}
    </div>
  );
}
```

---

#### 3. Tworzenie/Edycja sesji treningowej

##### `/dashboard/sessions/new` - Nowa sesja

**Komponenty hierarchia**:
```
WorkoutSessionPage (Client Component)
└── WorkoutSessionForm (useForm + FormProvider)
    ├── MetadataSection
    │   ├── DatePicker (default: today)
    │   ├── Input (name) - optional
    │   └── Textarea (notes) - optional
    ├── ExercisesSection
    │   ├── ExerciseSelector (dropdown + search)
    │   └── WorkoutExerciseList (useFieldArray)
    │       └── WorkoutExerciseItem (każde ćwiczenie)
    │           ├── ExerciseHeader (nazwa + delete button)
    │           └── ExerciseSetList (useFieldArray)
    │               ├── ExerciseSetRow (każda seria)
    │               │   ├── Input (setsCount)
    │               │   ├── Input (reps)
    │               │   ├── Input (weightKg)
    │               │   └── DeleteButton
    │               └── AddSetButton
    └── FormActions
        ├── SaveButton
        └── CancelButton
```

**Form State Management (React Hook Form)**:
```typescript
interface WorkoutSessionFormData {
  date: string;
  name?: string;
  notes?: string;
  exercises: {
    exerciseId: string;
    sets: {
      setsCount: number;
      reps: number;
      weightKg: number;
    }[];
  }[];
}

const form = useForm<WorkoutSessionFormData>({
  resolver: zodResolver(workoutSessionSchema),
  defaultValues: {
    date: new Date().toISOString().split('T')[0],
    name: '',
    notes: '',
    exercises: []
  }
});

const { fields: exercises, append, remove } = useFieldArray({
  control: form.control,
  name: 'exercises'
});
```

**API Integration Flow**:
1. User fills form (all data held in React Hook Form state)
2. Click "Zapisz sesję"
3. Submit handler:
   ```typescript
   async function onSubmit(data: WorkoutSessionFormData) {
     try {
       // 1. Create session
       const session = await apiClient.post('/workout-sessions', {
         date: data.date,
         name: data.name,
         notes: data.notes
       });
       
       // 2. Add exercises with sets
       for (const exercise of data.exercises) {
         await apiClient.post('/workout-exercises', {
           workoutSessionId: session.id,
           exerciseId: exercise.exerciseId,
           sets: exercise.sets // Optional array
         });
       }
       
       // 3. Success
       toast.success('Sesja została zapisana');
       router.push(`/dashboard/sessions/${session.id}`);
     } catch (error) {
       // Handle errors
       if (error.status === 400) {
         // Display validation errors
       }
     }
   }
   ```

**Walidacje (Zod)**:
```typescript
const exerciseSetSchema = z.object({
  setsCount: z.number().min(1).int(),
  reps: z.number().min(1).max(100).int(),
  weightKg: z.number().min(0).max(500)
});

const workoutExerciseSchema = z.object({
  exerciseId: z.string().uuid(),
  sets: z.array(exerciseSetSchema).min(1).max(20)
});

const workoutSessionSchema = z.object({
  date: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
  name: z.string().max(255).optional(),
  notes: z.string().optional(),
  exercises: z.array(workoutExerciseSchema).max(15)
});
```

**Exercise Selector (Client-side filtering)**:
```typescript
function ExerciseSelector({ onSelect }: ExerciseSelectorProps) {
  const { exercises } = useExercises(); // Cached from localStorage
  const [search, setSearch] = useState('');
  const [categoryFilter, setCategoryFilter] = useState<string | null>(null);
  
  const filteredExercises = useMemo(() => {
    return exercises.filter(ex => {
      const matchesSearch = ex.name.toLowerCase().includes(search.toLowerCase());
      const matchesCategory = !categoryFilter || ex.muscleCategoryId === categoryFilter;
      return matchesSearch && matchesCategory;
    });
  }, [exercises, search, categoryFilter]);
  
  return (
    <div>
      <Input 
        placeholder="Szukaj ćwiczenia..." 
        value={search}
        onChange={(e) => setSearch(e.target.value)}
      />
      <CategoryFilter value={categoryFilter} onChange={setCategoryFilter} />
      <ExerciseList exercises={filteredExercises} onSelect={onSelect} />
    </div>
  );
}
```

**Draft Auto-save (localStorage)**:
```typescript
// Save draft on form change (debounced)
useEffect(() => {
  const subscription = form.watch((value) => {
    const timeoutId = setTimeout(() => {
      localStorage.setItem('workout-draft', JSON.stringify(value));
    }, 1000);
    return () => clearTimeout(timeoutId);
  });
  return () => subscription.unsubscribe();
}, [form]);

// Restore draft on mount
useEffect(() => {
  const draft = localStorage.getItem('workout-draft');
  if (draft) {
    const shouldRestore = confirm('Znaleziono niezapisaną sesję. Czy chcesz ją przywrócić?');
    if (shouldRestore) {
      form.reset(JSON.parse(draft));
    } else {
      localStorage.removeItem('workout-draft');
    }
  }
}, []);

// Clear draft on successful submit
function onSubmit() {
  // ... API calls
  localStorage.removeItem('workout-draft');
}
```

**Before Unload Protection**:
```typescript
useEffect(() => {
  const handleBeforeUnload = (e: BeforeUnloadEvent) => {
    if (form.formState.isDirty) {
      e.preventDefault();
      e.returnValue = 'Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?';
    }
  };
  
  window.addEventListener('beforeunload', handleBeforeUnload);
  return () => window.removeEventListener('beforeunload', handleBeforeUnload);
}, [form.formState.isDirty]);
```

---

##### `/dashboard/sessions/[id]` - Widok sesji (read-only)

**Server Component**:
```typescript
async function WorkoutSessionPage({ params }: { params: { id: string } }) {
  const session = await fetch(`/workout-sessions/${params.id}`);
  
  if (!session) {
    notFound();
  }
  
  return (
    <div>
      <SessionHeader session={session} />
      <SessionMetadata session={session} />
      <ExercisesList exercises={session.workoutExercises} />
      <SessionActions sessionId={session.id} />
    </div>
  );
}
```

**Komponenty**:
- `SessionHeader` - data, nazwa, edit/delete buttons
- `SessionMetadata` - notatki
- `ExercisesList` - lista ćwiczeń z seriami (read-only)
  - Format: "Wyciskanie sztangi: 3x10@70kg, 2x8@80kg"
- `SessionActions` - Edit button → `/dashboard/sessions/[id]/edit`, Delete button

**API Integration**:
- GET `/workout-sessions/{id}` (SSR)

---

##### `/dashboard/sessions/[id]/edit` - Edycja sesji

**Podobna struktura do `/new`**, ale:
- Initial data loaded from API
- Separate save for metadata vs exercises

**Form separation**:
1. **Metadata form** (date, name, notes)
   - Standalone save button
   - PUT `/workout-sessions/{id}`

2. **Exercises list** (inline editing)
   - Each set row editable
   - Debounced auto-save (500ms)
   - PUT `/workout-exercises/{workoutExerciseId}` with updated sets array

**Debounced Auto-save**:
```typescript
function ExerciseSetRow({ workoutExerciseId, set, index }: Props) {
  const [localValue, setLocalValue] = useState(set);
  const debouncedSave = useDebouncedCallback(
    async (updatedSets) => {
      await apiClient.put(`/workout-exercises/${workoutExerciseId}`, {
        sets: updatedSets
      });
      toast.success('Zmiany zapisane', { duration: 1000 });
    },
    500
  );
  
  const handleChange = (field: keyof ExerciseSet, value: number) => {
    const updated = { ...localValue, [field]: value };
    setLocalValue(updated);
    debouncedSave(updated);
  };
  
  return (
    <div className="grid grid-cols-4 gap-2">
      <Input value={localValue.setsCount} onChange={e => handleChange('setsCount', Number(e.target.value))} />
      <Input value={localValue.reps} onChange={e => handleChange('reps', Number(e.target.value))} />
      <Input value={localValue.weightKg} onChange={e => handleChange('weightKg', Number(e.target.value))} />
      <DeleteButton onClick={handleDelete} />
    </div>
  );
}
```

**Delete Exercise**:
```typescript
async function handleDeleteExercise(workoutExerciseId: string) {
  const confirmed = confirm('Czy na pewno chcesz usunąć to ćwiczenie?');
  if (!confirmed) return;
  
  await apiClient.delete(`/workout-exercises/${workoutExerciseId}`);
  toast.success('Ćwiczenie usunięte');
  router.refresh(); // Revalidate server component
}
```

**Delete Session**:
```typescript
async function handleDeleteSession(sessionId: string) {
  const confirmed = confirm('Czy na pewno chcesz usunąć całą sesję treningową? Ta operacja jest nieodwracalna.');
  if (!confirmed) return;
  
  await apiClient.delete(`/workout-sessions/${sessionId}`);
  toast.success('Sesja została usunięta');
  router.push('/dashboard');
}
```

---

#### 4. Historia treningów (`/dashboard/history`)

**Komponenty**:
```
HistoryPage (Client Component)
├── HistoryFilters
│   └── DateFilter (7/30/90 days buttons)
├── WorkoutSessionList (infinite scroll)
│   └── SessionCard (expandable)
│       ├── SessionSummary (collapsed)
│       └── SessionDetails (expanded)
│           ├── ExercisesList
│           └── SessionActions (edit/delete)
└── LoadMoreTrigger (Intersection Observer)
```

**Infinite Scroll Implementation**:
```typescript
function HistoryPage() {
  const [dateFilter, setDateFilter] = useState<DateFilter | null>(null);
  
  // SWR Infinite
  const { data, size, setSize, isLoading, isValidating } = useSWRInfinite(
    (index) => {
      const params = new URLSearchParams({
        limit: '20',
        offset: String(index * 20),
        sortBy: 'date',
        sortOrder: 'desc'
      });
      
      if (dateFilter) {
        params.set('dateFrom', dateFilter.from);
        params.set('dateTo', dateFilter.to);
      }
      
      return `/workout-sessions?${params}`;
    },
    fetcher
  );
  
  // Flatten data
  const sessions = data ? data.flatMap(page => page.data) : [];
  const hasMore = data?.[data.length - 1]?.meta.total > sessions.length;
  
  // Intersection Observer
  const loadMoreRef = useRef<HTMLDivElement>(null);
  useEffect(() => {
    if (!loadMoreRef.current || !hasMore) return;
    
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting) {
          setSize(size + 1);
        }
      },
      { threshold: 0.8 }
    );
    
    observer.observe(loadMoreRef.current);
    return () => observer.disconnect();
  }, [hasMore, size]);
  
  return (
    <div>
      <HistoryFilters value={dateFilter} onChange={setDateFilter} />
      
      {isLoading && <SkeletonList count={5} />}
      
      <div className="space-y-4">
        {sessions.map(session => (
          <SessionCard key={session.id} session={session} />
        ))}
      </div>
      
      {hasMore && (
        <div ref={loadMoreRef} className="py-8">
          {isValidating && <Spinner />}
        </div>
      )}
      
      {!hasMore && sessions.length > 0 && (
        <p className="text-center text-gray-500 py-4">
          Wszystkie sesje zostały załadowane
        </p>
      )}
      
      {!isLoading && sessions.length === 0 && (
        <EmptyState message="Nie masz jeszcze żadnych sesji treningowych" />
      )}
    </div>
  );
}
```

**SessionCard (Expandable)**:
```typescript
function SessionCard({ session }: { session: WorkoutSessionSummary }) {
  const [expanded, setExpanded] = useState(false);
  const { data: details, isLoading } = useSWR(
    expanded ? `/workout-sessions/${session.id}` : null
  );
  
  return (
    <div className="bg-white rounded-lg shadow p-4">
      {/* Collapsed view */}
      <div 
        className="flex justify-between items-center cursor-pointer"
        onClick={() => setExpanded(!expanded)}
      >
        <div>
          <h3 className="font-semibold">{session.date}</h3>
          {session.name && <p className="text-sm text-gray-600">{session.name}</p>}
          <p className="text-xs text-gray-500">{session.exerciseCount} ćwiczeń</p>
        </div>
        <ChevronIcon className={expanded ? 'rotate-180' : ''} />
      </div>
      
      {/* Expanded view */}
      {expanded && (
        <div className="mt-4 border-t pt-4">
          {isLoading && <Spinner />}
          {details && (
            <>
              {details.notes && (
                <p className="text-sm text-gray-700 mb-4">{details.notes}</p>
              )}
              <ExercisesList exercises={details.workoutExercises} />
              <div className="flex gap-2 mt-4">
                <Link href={`/dashboard/sessions/${session.id}/edit`}>
                  <Button variant="outline">Edytuj</Button>
                </Link>
                <Button 
                  variant="destructive" 
                  onClick={() => handleDelete(session.id)}
                >
                  Usuń
                </Button>
              </div>
            </>
          )}
        </div>
      )}
    </div>
  );
}
```

**Date Filters**:
```typescript
function HistoryFilters({ value, onChange }: FiltersProps) {
  const presets = [
    { label: 'Ostatnie 7 dni', days: 7 },
    { label: 'Ostatnie 30 dni', days: 30 },
    { label: 'Ostatnie 90 dni', days: 90 },
  ];
  
  const handlePreset = (days: number) => {
    const today = new Date();
    const from = new Date(today);
    from.setDate(today.getDate() - days);
    
    onChange({
      from: from.toISOString().split('T')[0],
      to: today.toISOString().split('T')[0]
    });
  };
  
  return (
    <div className="flex gap-2 mb-4">
      <Button
        variant={value === null ? 'primary' : 'outline'}
        onClick={() => onChange(null)}
      >
        Wszystkie
      </Button>
      {presets.map(preset => (
        <Button
          key={preset.days}
          variant={value?.days === preset.days ? 'primary' : 'outline'}
          onClick={() => handlePreset(preset.days)}
        >
          {preset.label}
        </Button>
      ))}
    </div>
  );
}
```

---

#### 5. Profil użytkownika (`/dashboard/profile`)

**Server Component**:
```typescript
async function ProfilePage() {
  const user = await getCurrentUser(); // GET /auth/me
  
  return (
    <div className="max-w-2xl mx-auto p-4">
      <h1 className="text-2xl font-bold mb-6">Profil użytkownika</h1>
      
      <div className="bg-white rounded-lg shadow p-6 space-y-4">
        <div>
          <label className="text-sm text-gray-600">Email</label>
          <p className="font-medium">{user.username}</p>
        </div>
        
        <div>
          <label className="text-sm text-gray-600">Data rejestracji</label>
          <p className="font-medium">
            {new Date(user.createdAt).toLocaleDateString('pl-PL')}
          </p>
        </div>
        
        <Separator />
        
        <LogoutButton />
      </div>
    </div>
  );
}
```

**LogoutButton (Client Component)**:
```typescript
'use client';

function LogoutButton() {
  const { logout } = useAuth();
  const router = useRouter();
  
  const handleLogout = async () => {
    logout(); // Clear token from localStorage + AuthContext
    router.push('/login');
  };
  
  return (
    <Button 
      variant="destructive" 
      onClick={handleLogout}
      className="w-full"
    >
      Wyloguj się
    </Button>
  );
}
```

---

### Strategia integracji z API i zarządzania stanem

#### API Client Implementation

**`lib/api.ts`**:
```typescript
const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/api/v1';

class ApiClient {
  private getAuthToken(): string | null {
    if (typeof window === 'undefined') return null;
    return localStorage.getItem('auth_token');
  }
  
  private async request<T>(
    endpoint: string,
    options?: RequestInit
  ): Promise<T> {
    const token = this.getAuthToken();
    
    const response = await fetch(`${API_URL}${endpoint}`, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...(token && { Authorization: `Bearer ${token}` }),
        ...options?.headers,
      },
    });
    
    // Handle errors
    if (!response.ok) {
      if (response.status === 401) {
        // Unauthorized - redirect to login
        if (typeof window !== 'undefined') {
          localStorage.removeItem('auth_token');
          window.location.href = '/login';
        }
        throw new Error('Unauthorized');
      }
      
      if (response.status === 400) {
        const error = await response.json();
        throw new ValidationError(error.message, error.errors);
      }
      
      const error = await response.json().catch(() => ({ message: 'Unknown error' }));
      throw new ApiError(error.message, response.status);
    }
    
    // Handle 204 No Content
    if (response.status === 204) {
      return null as T;
    }
    
    return response.json();
  }
  
  async get<T>(endpoint: string): Promise<T> {
    return this.request<T>(endpoint, { method: 'GET' });
  }
  
  async post<T>(endpoint: string, data?: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
    });
  }
  
  async put<T>(endpoint: string, data: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }
  
  async delete<T>(endpoint: string): Promise<T> {
    return this.request<T>(endpoint, { method: 'DELETE' });
  }
}

export const apiClient = new ApiClient();

// Custom errors
export class ApiError extends Error {
  constructor(message: string, public status: number) {
    super(message);
    this.name = 'ApiError';
  }
}

export class ValidationError extends ApiError {
  constructor(message: string, public errors: Record<string, string[]>) {
    super(message, 400);
    this.name = 'ValidationError';
  }
}
```

---

#### Auth Context

**`context/AuthContext.tsx`**:
```typescript
'use client';

interface User {
  id: string;
  username: string;
  createdAt: string;
}

interface AuthContextType {
  user: User | null;
  token: string | null;
  login: (email: string, password: string) => Promise<void>;
  register: (email: string, password: string, passwordConfirmation: string) => Promise<void>;
  logout: () => void;
  isLoading: boolean;
}

const AuthContext = createContext<AuthContextType | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  
  // Initialize from localStorage
  useEffect(() => {
    const storedToken = localStorage.getItem('auth_token');
    if (storedToken) {
      setToken(storedToken);
      // Fetch user data
      apiClient.get<User>('/auth/me')
        .then(setUser)
        .catch(() => {
          localStorage.removeItem('auth_token');
          setToken(null);
        })
        .finally(() => setIsLoading(false));
    } else {
      setIsLoading(false);
    }
  }, []);
  
  const login = async (email: string, password: string) => {
    const response = await apiClient.post<{ user: User; token: string }>('/auth/login', {
      username: email,
      password,
    });
    
    setUser(response.user);
    setToken(response.token);
    localStorage.setItem('auth_token', response.token);
  };
  
  const register = async (email: string, password: string, passwordConfirmation: string) => {
    const response = await apiClient.post<{ user: User; token: string }>('/auth/register', {
      email,
      password,
      passwordConfirmation,
    });
    
    setUser(response.user);
    setToken(response.token);
    localStorage.setItem('auth_token', response.token);
  };
  
  const logout = () => {
    setUser(null);
    setToken(null);
    localStorage.removeItem('auth_token');
  };
  
  return (
    <AuthContext.Provider value={{ user, token, login, register, logout, isLoading }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
}
```

---

#### Middleware (Route Guards)

**`middleware.ts`**:
```typescript
import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

export function middleware(request: NextRequest) {
  const token = request.cookies.get('auth_token')?.value;
  const isAuthPage = request.nextUrl.pathname.startsWith('/login') || 
                     request.nextUrl.pathname.startsWith('/register');
  const isDashboardPage = request.nextUrl.pathname.startsWith('/dashboard');
  
  // Redirect to login if accessing dashboard without token
  if (isDashboardPage && !token) {
    return NextResponse.redirect(new URL('/login', request.url));
  }
  
  // Redirect to dashboard if accessing auth pages with valid token
  if (isAuthPage && token) {
    return NextResponse.redirect(new URL('/dashboard', request.url));
  }
  
  return NextResponse.next();
}

export const config = {
  matcher: ['/dashboard/:path*', '/login', '/register'],
};
```

**Note**: Powyższy middleware wymaga cookies. Dla localStorage approach, użyj client-side route guards:

**`components/layout/ProtectedRoute.tsx`**:
```typescript
'use client';

export function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { user, isLoading } = useAuth();
  const router = useRouter();
  
  useEffect(() => {
    if (!isLoading && !user) {
      router.push('/login');
    }
  }, [user, isLoading, router]);
  
  if (isLoading) {
    return <LoadingScreen />;
  }
  
  if (!user) {
    return null;
  }
  
  return <>{children}</>;
}
```

Wrap dashboard layout:
```typescript
// app/dashboard/layout.tsx
export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  return (
    <ProtectedRoute>
      <div className="min-h-screen">
        <Header />
        <main>{children}</main>
        <BottomNav />
      </div>
    </ProtectedRoute>
  );
}
```

---

#### SWR/React Query Setup

**SWR Configuration** (`lib/swr-config.ts`):
```typescript
import { SWRConfig } from 'swr';
import { apiClient } from './api';

const fetcher = (url: string) => apiClient.get(url);

export function SWRProvider({ children }: { children: React.ReactNode }) {
  return (
    <SWRConfig
      value={{
        fetcher,
        revalidateOnFocus: true,
        revalidateOnReconnect: true,
        shouldRetryOnError: false,
        dedupingInterval: 2000,
        errorRetryCount: 3,
      }}
    >
      {children}
    </SWRConfig>
  );
}
```

**Custom Hooks**:

**`hooks/useWorkoutSessions.ts`**:
```typescript
import useSWR from 'swr';
import useSWRInfinite from 'swr/infinite';

export function useWorkoutSessions(params?: {
  limit?: number;
  offset?: number;
  dateFrom?: string;
  dateTo?: string;
  sortBy?: 'date' | 'createdAt';
  sortOrder?: 'asc' | 'desc';
}) {
  const queryString = new URLSearchParams(params as any).toString();
  const { data, error, isLoading, mutate } = useSWR<WorkoutSessionList>(
    `/workout-sessions?${queryString}`
  );
  
  return {
    sessions: data?.data ?? [],
    meta: data?.meta,
    isLoading,
    error,
    mutate,
  };
}

export function useWorkoutSessionsInfinite(filters?: {
  dateFrom?: string;
  dateTo?: string;
}) {
  const getKey = (pageIndex: number, previousPageData: WorkoutSessionList | null) => {
    if (previousPageData && !previousPageData.data.length) return null;
    
    const params = new URLSearchParams({
      limit: '20',
      offset: String(pageIndex * 20),
      sortBy: 'date',
      sortOrder: 'desc',
      ...filters,
    });
    
    return `/workout-sessions?${params}`;
  };
  
  const { data, error, size, setSize, isLoading, isValidating, mutate } = useSWRInfinite<WorkoutSessionList>(
    getKey
  );
  
  const sessions = data ? data.flatMap(page => page.data) : [];
  const hasMore = data?.[data.length - 1]?.meta.total > sessions.length;
  
  return {
    sessions,
    isLoading,
    isLoadingMore: isValidating,
    error,
    hasMore,
    loadMore: () => setSize(size + 1),
    mutate,
  };
}

export function useWorkoutSession(id: string | null) {
  const { data, error, isLoading, mutate } = useSWR<WorkoutSessionDetail>(
    id ? `/workout-sessions/${id}` : null
  );
  
  return {
    session: data,
    isLoading,
    error,
    mutate,
  };
}
```

**`hooks/useExercises.ts`**:
```typescript
import useSWR from 'swr';
import { useEffect } from 'react';

const CACHE_KEY = 'exercises_cache';
const CACHE_DURATION = 24 * 60 * 60 * 1000; // 24 hours

export function useExercises() {
  const { data, error, isLoading } = useSWR<Exercise[]>('/exercises', {
    // Try to load from cache first
    fallbackData: loadFromCache(),
    revalidateOnFocus: false,
    revalidateOnReconnect: false,
  });
  
  // Save to cache when data changes
  useEffect(() => {
    if (data) {
      saveToCache(data);
    }
  }, [data]);
  
  return {
    exercises: data ?? [],
    isLoading,
    error,
  };
}

function loadFromCache(): Exercise[] | undefined {
  if (typeof window === 'undefined') return undefined;
  
  try {
    const cached = localStorage.getItem(CACHE_KEY);
    if (!cached) return undefined;
    
    const { data, timestamp } = JSON.parse(cached);
    const age = Date.now() - timestamp;
    
    if (age > CACHE_DURATION) {
      localStorage.removeItem(CACHE_KEY);
      return undefined;
    }
    
    return data;
  } catch {
    return undefined;
  }
}

function saveToCache(data: Exercise[]) {
  if (typeof window === 'undefined') return;
  
  try {
    localStorage.setItem(CACHE_KEY, JSON.stringify({
      data,
      timestamp: Date.now(),
    }));
  } catch {
    // Ignore cache save errors
  }
}
```

**`hooks/useExerciseStatistics.ts`**:
```typescript
import useSWR from 'swr';

export function useExerciseStatistics(
  exerciseId: string | null,
  params?: {
    dateFrom?: string;
    dateTo?: string;
    limit?: number;
  }
) {
  const queryString = params ? `?${new URLSearchParams(params as any)}` : '';
  
  const { data, error, isLoading } = useSWR<ExerciseStatistics>(
    exerciseId ? `/statistics/exercise/${exerciseId}${queryString}` : null
  );
  
  return {
    statistics: data,
    isLoading,
    error,
  };
}
```

---

### Responsywność, dostępność i bezpieczeństwo

#### Responsywność

**Breakpoints (Tailwind)**:
```typescript
// tailwind.config.js
module.exports = {
  theme: {
    screens: {
      'sm': '640px',   // Mobile landscape
      'md': '768px',   // Tablet
      'lg': '1024px',  // Desktop
      'xl': '1280px',  // Large desktop
      '2xl': '1536px', // Extra large
    },
  },
};
```

**Mobile-First Classes**:
```tsx
// Example: Dashboard layout
<div className="
  container 
  mx-auto 
  px-4 sm:px-6 lg:px-8 
  py-4 sm:py-6 lg:py-8
">
  <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold">
    Dashboard
  </h1>
</div>

// Example: Form grid
<div className="
  grid 
  grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 
  gap-4 sm:gap-6
">
  {/* Form fields */}
</div>

// Example: Bottom navigation (mobile only)
<nav className="
  lg:hidden 
  fixed bottom-0 inset-x-0 
  bg-white border-t 
  z-50
">
  {/* Navigation items */}
</nav>

// Example: Sidebar navigation (desktop only)
<nav className="
  hidden lg:block 
  fixed left-0 top-0 bottom-0 
  w-64 
  bg-white border-r
">
  {/* Navigation items */}
</nav>
```

**Touch-Friendly Sizes**:
```tsx
// Buttons (min 44x44px for touch targets)
<Button className="min-h-[44px] min-w-[44px] px-4 py-3">
  Dodaj serię
</Button>

// Form inputs
<Input className="h-12 text-base" /> {/* Larger for mobile */}

// Cards/clickable areas
<div className="p-4 min-h-[60px]"> {/* Adequate touch area */}
```

---

#### Dostępność (a11y)

**Semantic HTML**:
```tsx
<nav aria-label="Główna nawigacja">
  <ul>
    <li><a href="/dashboard">Dashboard</a></li>
  </ul>
</nav>

<main>
  <h1>Dashboard</h1>
  {/* Main content */}
</main>

<footer>
  {/* Footer content */}
</footer>
```

**ARIA Labels**:
```tsx
// Buttons without text
<button aria-label="Usuń ćwiczenie" onClick={handleDelete}>
  <TrashIcon />
</button>

// Form inputs
<label htmlFor="email">Email</label>
<input 
  id="email" 
  type="email" 
  aria-describedby="email-error"
  aria-invalid={!!errors.email}
/>
{errors.email && (
  <p id="email-error" role="alert" className="text-red-600">
    {errors.email}
  </p>
)}

// Loading states
<div role="status" aria-live="polite" aria-busy={isLoading}>
  {isLoading ? 'Ładowanie...' : 'Załadowano'}
</div>
```

**Keyboard Navigation**:
```tsx
// Custom dropdown
function Dropdown({ items, onSelect }: Props) {
  const [isOpen, setIsOpen] = useState(false);
  const [focusedIndex, setFocusedIndex] = useState(0);
  
  const handleKeyDown = (e: KeyboardEvent) => {
    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        setFocusedIndex((i) => Math.min(i + 1, items.length - 1));
        break;
      case 'ArrowUp':
        e.preventDefault();
        setFocusedIndex((i) => Math.max(i - 1, 0));
        break;
      case 'Enter':
        e.preventDefault();
        onSelect(items[focusedIndex]);
        setIsOpen(false);
        break;
      case 'Escape':
        setIsOpen(false);
        break;
    }
  };
  
  return (
    <div onKeyDown={handleKeyDown}>
      {/* Dropdown implementation */}
    </div>
  );
}
```

**Focus Management**:
```tsx
// Modal/Dialog
function Modal({ isOpen, onClose, children }: Props) {
  const modalRef = useRef<HTMLDivElement>(null);
  const previousFocusRef = useRef<HTMLElement | null>(null);
  
  useEffect(() => {
    if (isOpen) {
      // Save current focus
      previousFocusRef.current = document.activeElement as HTMLElement;
      
      // Focus first focusable element in modal
      const firstFocusable = modalRef.current?.querySelector<HTMLElement>(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      firstFocusable?.focus();
    } else {
      // Restore focus
      previousFocusRef.current?.focus();
    }
  }, [isOpen]);
  
  return (
    <div 
      ref={modalRef}
      role="dialog" 
      aria-modal="true"
      aria-labelledby="modal-title"
    >
      {children}
    </div>
  );
}
```

**Color Contrast**:
```tsx
// Tailwind config for WCAG AA compliance
module.exports = {
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#2563eb', // 4.5:1 contrast on white
          dark: '#1e40af',    // For dark mode
        },
        error: {
          DEFAULT: '#dc2626', // 4.5:1 contrast
          light: '#fca5a5',
        },
      },
    },
  },
};
```

---

#### Bezpieczeństwo

**XSS Protection**:
```tsx
// React auto-escapes by default
<p>{userInput}</p> // Safe

// For HTML content, use DOMPurify
import DOMPurify from 'dompurify';

function NotesDisplay({ notes }: { notes: string }) {
  const sanitized = DOMPurify.sanitize(notes, {
    ALLOWED_TAGS: ['b', 'i', 'em', 'strong', 'p', 'br'],
  });
  
  return <div dangerouslySetInnerHTML={{ __html: sanitized }} />;
}
```

**Input Validation**:
```tsx
// Client-side (immediate feedback)
const schema = z.object({
  weightKg: z.number()
    .min(0, 'Ciężar nie może być ujemny')
    .max(500, 'Ciężar nie może przekraczać 500kg'),
  reps: z.number()
    .int('Liczba powtórzeń musi być liczbą całkowitą')
    .min(1, 'Minimum 1 powtórzenie')
    .max(100, 'Maksimum 100 powtórzeń'),
});

// Always validate on backend (final authority)
```

**Sensitive Data**:
```tsx
// NEVER log sensitive data
console.log({ email, password }); // ❌ BAD

// Log only non-sensitive info
console.log({ email }); // ✅ OK (email is not secret)
console.log('Login attempt'); // ✅ OK

// Don't expose tokens in URLs
// ❌ BAD: /api/user?token=abc123
// ✅ GOOD: Authorization header
```

**API Error Handling**:
```tsx
try {
  await apiClient.post('/workout-sessions', data);
} catch (error) {
  if (error instanceof ValidationError) {
    // Display field errors
    Object.entries(error.errors).forEach(([field, messages]) => {
      form.setError(field, { message: messages[0] });
    });
  } else if (error instanceof ApiError) {
    // Display general error
    toast.error(error.message);
  } else {
    // Unknown error
    toast.error('Wystąpił nieoczekiwany błąd');
    console.error(error); // Log for debugging
  }
}
```

**Auto Logout on 401**:
```tsx
// In API client
if (response.status === 401) {
  // Clear auth state
  localStorage.removeItem('auth_token');
  
  // Redirect to login
  if (typeof window !== 'undefined') {
    window.location.href = '/login';
  }
  
  throw new Error('Unauthorized');
}
```

---

### Obsługa błędów i stanów wyjątkowych

#### Error Boundaries

**Global Error Boundary**:
```tsx
// app/error.tsx (Next.js 14 convention)
'use client';

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return (
    <div className="min-h-screen flex items-center justify-center p-4">
      <div className="text-center">
        <h1 className="text-2xl font-bold text-red-600 mb-4">
          Coś poszło nie tak
        </h1>
        <p className="text-gray-600 mb-6">
          Przepraszamy, wystąpił nieoczekiwany błąd.
        </p>
        <Button onClick={reset}>Spróbuj ponownie</Button>
      </div>
    </div>
  );
}
```

**Component-Level Error Boundary**:
```tsx
class FormErrorBoundary extends React.Component<
  { children: React.ReactNode },
  { hasError: boolean }
> {
  state = { hasError: false };
  
  static getDerivedStateFromError() {
    return { hasError: true };
  }
  
  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error('Form error:', error, errorInfo);
  }
  
  render() {
    if (this.state.hasError) {
      return (
        <div className="p-4 bg-red-50 border border-red-200 rounded">
          <p className="text-red-600">
            Formularz napotkał błąd. Odśwież stronę i spróbuj ponownie.
          </p>
        </div>
      );
    }
    
    return this.props.children;
  }
}
```

---

#### Loading States

**Skeleton Screens**:
```tsx
function SessionCardSkeleton() {
  return (
    <div className="bg-white rounded-lg shadow p-4 animate-pulse">
      <div className="h-6 bg-gray-200 rounded w-1/3 mb-2"></div>
      <div className="h-4 bg-gray-200 rounded w-1/2 mb-1"></div>
      <div className="h-3 bg-gray-200 rounded w-1/4"></div>
    </div>
  );
}

function SkeletonList({ count }: { count: number }) {
  return (
    <div className="space-y-4">
      {Array.from({ length: count }).map((_, i) => (
        <SessionCardSkeleton key={i} />
      ))}
    </div>
  );
}
```

**Button Loading State**:
```tsx
function Button({ 
  children, 
  isLoading, 
  disabled,
  ...props 
}: ButtonProps) {
  return (
    <button 
      disabled={disabled || isLoading}
      className="relative"
      {...props}
    >
      {isLoading && (
        <span className="absolute inset-0 flex items-center justify-center">
          <Spinner className="w-5 h-5" />
        </span>
      )}
      <span className={isLoading ? 'invisible' : ''}>
        {children}
      </span>
    </button>
  );
}
```

**Suspense (Next.js 14)**:
```tsx
// app/dashboard/page.tsx
import { Suspense } from 'react';

export default function DashboardPage() {
  return (
    <div>
      <h1>Dashboard</h1>
      
      <Suspense fallback={<SkeletonList count={5} />}>
        <RecentSessions />
      </Suspense>
      
      <Suspense fallback={<ChartSkeleton />}>
        <StatsPanel />
      </Suspense>
    </div>
  );
}
```

---

#### Empty States

```tsx
function EmptyState({ 
  icon: Icon, 
  title, 
  description, 
  action 
}: EmptyStateProps) {
  return (
    <div className="text-center py-12">
      {Icon && <Icon className="w-16 h-16 mx-auto text-gray-400 mb-4" />}
      <h3 className="text-lg font-medium text-gray-900 mb-2">{title}</h3>
      <p className="text-gray-600 mb-6">{description}</p>
      {action}
    </div>
  );
}

// Usage
<EmptyState
  icon={WorkoutIcon}
  title="Brak sesji treningowych"
  description="Nie masz jeszcze żadnych sesji. Zacznij od dodania pierwszej!"
  action={
    <Link href="/dashboard/sessions/new">
      <Button>Dodaj pierwszą sesję</Button>
    </Link>
  }
/>
```

---

### Struktura katalogów frontend

```
frontend/
├── public/
│   ├── icons/
│   └── images/
├── src/
│   ├── app/                              # Next.js 14 App Router
│   │   ├── (auth)/                       # Route group (niechronione)
│   │   │   ├── login/
│   │   │   │   └── page.tsx
│   │   │   └── register/
│   │   │       └── page.tsx
│   │   ├── dashboard/                    # Route group (chronione)
│   │   │   ├── layout.tsx                # Dashboard layout (ProtectedRoute + nav)
│   │   │   ├── page.tsx                  # Dashboard główny
│   │   │   ├── history/
│   │   │   │   └── page.tsx
│   │   │   ├── profile/
│   │   │   │   └── page.tsx
│   │   │   └── sessions/
│   │   │       ├── new/
│   │   │       │   └── page.tsx
│   │   │       └── [id]/
│   │   │           ├── page.tsx          # Read-only view
│   │   │           └── edit/
│   │   │               └── page.tsx
│   │   ├── layout.tsx                    # Root layout (AuthProvider, SWRProvider)
│   │   ├── error.tsx                     # Global error boundary
│   │   ├── not-found.tsx                 # 404 page
│   │   └── globals.css                   # Global styles + Tailwind
│   ├── components/
│   │   ├── ui/                           # Reusable UI primitives
│   │   │   ├── Button.tsx
│   │   │   ├── Input.tsx
│   │   │   ├── Card.tsx
│   │   │   ├── Modal.tsx
│   │   │   ├── Dropdown.tsx
│   │   │   ├── Spinner.tsx
│   │   │   └── Skeleton.tsx
│   │   ├── forms/                        # Form-specific components
│   │   │   ├── LoginForm.tsx
│   │   │   ├── RegisterForm.tsx
│   │   │   ├── WorkoutSessionForm.tsx
│   │   │   ├── WorkoutExerciseItem.tsx
│   │   │   ├── ExerciseSetList.tsx
│   │   │   ├── ExerciseSetRow.tsx
│   │   │   ├── ExerciseSelector.tsx
│   │   │   └── PasswordStrengthIndicator.tsx
│   │   ├── layout/                       # Layout components
│   │   │   ├── ProtectedRoute.tsx
│   │   │   ├── BottomNav.tsx
│   │   │   ├── Header.tsx
│   │   │   └── Sidebar.tsx
│   │   ├── charts/                       # Chart components
│   │   │   ├── ExerciseProgressChart.tsx
│   │   │   └── ChartSkeleton.tsx
│   │   ├── sessions/                     # Session-related components
│   │   │   ├── SessionCard.tsx
│   │   │   ├── SessionSummary.tsx
│   │   │   ├── SessionDetails.tsx
│   │   │   └── SessionActions.tsx
│   │   └── common/                       # Common components
│   │       ├── EmptyState.tsx
│   │       ├── ErrorMessage.tsx
│   │       ├── LoadingScreen.tsx
│   │       └── ConfirmDialog.tsx
│   ├── lib/
│   │   ├── api.ts                        # API client (fetch wrapper)
│   │   ├── types.ts                      # TypeScript types/interfaces
│   │   ├── utils.ts                      # Utility functions
│   │   ├── validations.ts                # Zod schemas
│   │   ├── constants.ts                  # App constants
│   │   └── swr-config.ts                 # SWR configuration
│   ├── hooks/
│   │   ├── useAuth.ts                    # Auth hook
│   │   ├── useWorkoutSessions.ts         # Workout sessions hooks
│   │   ├── useWorkoutSession.ts          # Single session hook
│   │   ├── useExercises.ts               # Exercises hook (with cache)
│   │   ├── useExerciseStatistics.ts      # Statistics hook
│   │   ├── useDebounce.ts                # Debounce hook
│   │   └── useIntersectionObserver.ts    # Intersection observer hook
│   ├── context/
│   │   └── AuthContext.tsx               # Auth context provider
│   └── middleware.ts                     # Next.js middleware (route guards)
├── .env.local                            # Environment variables
├── next.config.js                        # Next.js configuration
├── tailwind.config.js                    # Tailwind configuration
├── tsconfig.json                         # TypeScript configuration
└── package.json                          # Dependencies
```

---

### Strategia implementacji MVP

#### Faza 1: Foundation (tydzień 1-2)

**Setup projektu**:
- Initialize Next.js 14 with TypeScript
- Setup Tailwind CSS
- Configure ESLint + Prettier
- Setup environment variables

**Core infrastructure**:
- API client (`lib/api.ts`)
- TypeScript types (`lib/types.ts`)
- Auth context (`context/AuthContext.tsx`)
- Middleware dla route guards (`middleware.ts`)

**Basic UI components**:
- Button, Input, Card, Spinner, Skeleton
- Layout components (Header, BottomNav)

**Auth pages**:
- `/login` - Login form
- `/register` - Registration form with password validation

**Deliverable**: Working authentication flow (login/register/logout)

---

#### Faza 2: Core Features (tydzień 3-4)

**Dashboard**:
- Dashboard layout with navigation
- Recent sessions list (SSR)
- Stats panel structure (without chart initially)
- New session button

**Tworzenie sesji**:
- `/dashboard/sessions/new` page
- WorkoutSessionForm with metadata
- ExerciseSelector (client-side filtering)
- Dynamic exercise list with useFieldArray
- Dynamic set list with useFieldArray
- Draft auto-save to localStorage
- Before unload protection
- API integration (POST session + exercises)

**Słownik ćwiczeń**:
- GET `/exercises` with localStorage cache
- Exercise selector with search and category filter

**Deliverable**: Możliwość utworzenia pełnej sesji treningowej

---

#### Faza 3: History & Stats (tydzień 5)

**Historia treningów**:
- `/dashboard/history` page
- Infinite scroll z SWR
- Expandable session cards
- Date filters (7/30/90 days)
- Session details with edit/delete actions

**Widok sesji**:
- `/dashboard/sessions/[id]` - Read-only view
- `/dashboard/sessions/[id]/edit` - Edit mode
- Debounced auto-save for sets
- Delete exercise confirmation
- Delete session confirmation

**Statystyki**:
- Exercise progress chart (Recharts)
- GET `/statistics/exercise/{id}` integration
- Chart empty states

**Profil**:
- User profile page
- Logout functionality

**Deliverable**: Pełny CRUD dla sesji + statystyki + historia

---

#### Faza 4: Polish & Testing (tydzień 6)

**Error handling**:
- Error boundaries
- API error handling
- Validation errors display
- Network error detection

**Loading states**:
- Skeleton screens
- Loading indicators
- Suspense boundaries

**Empty states**:
- Dashboard empty state
- History empty state
- Stats empty state

**Responsywność**:
- Mobile optimization
- Tablet breakpoints
- Desktop layouts
- Touch-friendly interactions

**Accessibility**:
- Keyboard navigation
- ARIA labels
- Focus management
- Color contrast verification

**Testing**:
- Manual testing wszystkich user stories (US-001 do US-034)
- Edge cases testing
- Mobile device testing
- Cross-browser testing

**Deliverable**: Production-ready MVP

---

## Nierozwiązane kwestie

**Brak nierozwiązanych kwestii** - wszystkie kluczowe decyzje architektoniczne zostały podjęte i zaakceptowane zgodnie z rekomendacjami.

Projekt gotowy do implementacji zgodnie z zaplanowaną architekturą UI.

---

### Potencjalne obszary do rozważenia w przyszłości (poza MVP)

1. **Migracja tokenu JWT**:
   - Z localStorage na httpOnly cookies dla większego bezpieczeństwa
   - Wymaga zmian w API (set-cookie headers)

2. **Offline capabilities**:
   - Service Workers dla offline-first experience
   - Background sync dla wysyłania danych
   - IndexedDB dla lokalnej bazy danych

3. **PWA**:
   - Web manifest
   - Install prompt
   - App-like experience na mobile

4. **Internationalization (i18n)**:
   - next-i18next lub next-intl
   - Tłumaczenia UI i słownika ćwiczeń
   - Przygotowanie jest już w PRD

5. **Advanced analytics**:
   - Custom dashboards
   - Zaawansowane wykresy (volume, intensity)
   - Porównania okresów

6. **Data export**:
   - CSV/PDF export historii
   - GDPR compliance (data portability)

7. **Notifications**:
   - Push notifications dla przypomnień
   - Email notifications

8. **Social features**:
   - Sharing workouts
   - Following other users
   - Leaderboards

---

## Podsumowanie kluczowych technologii

| Obszar | Technologia | Uzasadnienie |
|--------|-------------|--------------|
| Framework | Next.js 14 App Router | SSR/CSR hybrid, modern React patterns |
| Język | TypeScript | Type safety, lepsze DX |
| Stylizacja | Tailwind CSS | Rapid development, responsive utilities |
| Formularze | React Hook Form + Zod | Performance, validation integration |
| State Management | SWR/React Query | Cache, revalidation, optimistic updates |
| Wykresy | Recharts | React-native, responsive, easy to use |
| Auth | Context API + localStorage | Simple, adequate for MVP |
| Routing | Next.js file-based | Convention over configuration |

---

**Dokument przygotowany**: 18 października 2025  
**Wersja**: 1.0  
**Status**: Zatwierdzony do implementacji

