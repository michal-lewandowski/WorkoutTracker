# WorkoutTracker Frontend

Frontend aplikacji WorkoutTracker - Next.js 14 z TypeScript i Tailwind CSS.

## 🛠️ Stack Technologiczny

- **Framework**: Next.js 14 (App Router)
- **Język**: TypeScript
- **Stylizacja**: Tailwind CSS
- **Formularze**: React Hook Form + Zod
- **Data Fetching**: SWR
- **Wykresy**: Recharts (będzie użyte w kolejnych fazach)

## 📦 Instalacja

### 1. Zainstaluj zależności

```bash
cd frontend
npm install
```

### 2. Skonfiguruj zmienne środowiskowe

Utwórz plik `.env.local` (jeśli jeszcze nie istnieje):

```bash
NEXT_PUBLIC_API_URL=http://localhost/api/v1
```

### 3. Uruchom serwer deweloperski

```bash
npm run dev
```

Aplikacja będzie dostępna pod adresem: `http://localhost:3000`

## 📁 Struktura Projektu

```
frontend/
├── src/
│   ├── app/                      # Next.js App Router
│   │   ├── (auth)/              # Auth pages (login, register)
│   │   │   ├── login/
│   │   │   └── register/
│   │   ├── dashboard/           # Dashboard pages (protected)
│   │   │   ├── history/
│   │   │   ├── profile/
│   │   │   └── sessions/
│   │   ├── layout.tsx           # Root layout
│   │   └── page.tsx             # Home page (redirect)
│   ├── components/
│   │   ├── ui/                  # Reusable UI components
│   │   │   ├── Button.tsx
│   │   │   ├── Input.tsx
│   │   │   ├── Card.tsx
│   │   │   ├── Spinner.tsx
│   │   │   └── Skeleton.tsx
│   │   ├── forms/               # Form components
│   │   │   ├── LoginForm.tsx
│   │   │   ├── RegisterForm.tsx
│   │   │   └── PasswordStrengthIndicator.tsx
│   │   ├── layout/              # Layout components
│   │   │   ├── ProtectedRoute.tsx
│   │   │   ├── Header.tsx
│   │   │   └── BottomNav.tsx
│   │   └── common/              # Common components
│   │       ├── EmptyState.tsx
│   │       └── ErrorMessage.tsx
│   ├── lib/
│   │   ├── api.ts               # API client
│   │   ├── types.ts             # TypeScript types
│   │   ├── validations.ts       # Zod schemas
│   │   ├── utils.ts             # Helper functions
│   │   └── swr-config.tsx       # SWR configuration
│   └── context/
│       └── AuthContext.tsx      # Auth context
├── public/                       # Static files
├── tailwind.config.js           # Tailwind configuration
├── tsconfig.json                # TypeScript configuration
└── package.json
```

## ✅ Stan Implementacji

### Faza 1: Foundation (UKOŃCZONA ✅)

- ✅ Setup projektu + zależności
- ✅ Konfiguracja Tailwind CSS
- ✅ API Client z obsługą błędów
- ✅ TypeScript types (bazując na Swagger)
- ✅ Auth Context (login, register, logout)
- ✅ SWR Provider
- ✅ Podstawowe komponenty UI
- ✅ Layout components
- ✅ Strony autentykacji (login/register)
- ✅ Walidacja formularzy (Zod)
- ✅ **Deliverable: Working authentication flow** 🎉

### Faza 2: Core Features (W PLANACH)

- Implementacja Dashboard
- Formularz tworzenia sesji treningowej
- Słownik ćwiczeń
- Dynamiczne dodawanie ćwiczeń i serii

### Faza 3: History & Stats (W PLANACH)

- Historia treningów (infinite scroll)
- Statystyki ćwiczeń (wykresy)
- Edycja sesji

### Faza 4: Polish & Testing (W PLANACH)

- Error boundaries
- Loading states
- Empty states
- Responsywność
- Accessibility
- Testing

## 🚀 Dostępne Skrypty

```bash
# Uruchom serwer deweloperski
npm run dev

# Zbuduj aplikację produkcyjną
npm run build

# Uruchom aplikację produkcyjną
npm start

# Sprawdź kod (lint)
npm run lint
```

## 🎨 Komponenty UI

### Button
Wielofunkcyjny przycisk z wariantami: primary, secondary, outline, destructive, ghost.

```tsx
<Button variant="primary" size="lg" isLoading={loading}>
  Zapisz
</Button>
```

### Input
Pole tekstowe z obsługą label, error messages, i accessibility.

```tsx
<Input
  label="Email"
  type="email"
  error={errors.email?.message}
  {...register('email')}
/>
```

### Card
Kontener dla sekcji treści z sub-komponentami.

```tsx
<Card>
  <CardHeader>Tytuł</CardHeader>
  <CardContent>Treść</CardContent>
  <CardFooter>Stopka</CardFooter>
</Card>
```

## 🔐 Autentykacja

Aplikacja używa JWT tokens przechowywanych w `localStorage`. Auth state jest zarządzany przez `AuthContext`.

```tsx
const { user, login, register, logout, isLoading } = useAuth();
```

Chronione routes są zabezpieczone przez `ProtectedRoute` component.

## 🌐 API Integration

API client automatycznie dodaje JWT token do requestów i obsługuje błędy:

```tsx
import { apiClient } from '@/lib/api';

// GET request
const data = await apiClient.get('/workout-sessions');

// POST request
const newSession = await apiClient.post('/workout-sessions', data);
```

## 📱 Responsywność

Aplikacja jest w pełni responsywna z mobile-first approach:
- Bottom navigation na mobile
- Touch-friendly controls (min 44x44px)
- Tailwind breakpoints: sm (640px), md (768px), lg (1024px), xl (1280px)

## 🎯 Następne Kroki

1. Zainstaluj zależności: `npm install`
2. Upewnij się, że backend Symfony działa
3. Uruchom frontend: `npm run dev`
4. Otwórz `http://localhost:3000`
5. Zarejestruj nowe konto lub zaloguj się

---

**Wersja**: 0.1.0 (Faza 1 ukończona)  
**Data**: Październik 2025

