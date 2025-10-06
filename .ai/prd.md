# Dokument wymagań produktu (PRD) - WorkoutTracker MVP

## 1. Przegląd produktu

WorkoutTracker to responsywna aplikacja webowa zaprojektowana z podejściem mobile-first, umożliwiająca użytkownikom łatwe śledzenie postępu w treningach oporowych. Aplikacja rozwiązuje problem niewygodnego rejestrowania treningów w tradycyjnych narzędziach takich jak notatniki czy arkusze kalkulacyjne.

Platforma umożliwia użytkownikom:
- Tworzenie i zarządzanie sesjami treningowymi bezpośrednio z telefonu podczas treningu
- Rejestrowanie wykonanych ćwiczeń wraz z szczegółami serii (powtórzenia, ciężar)
- Wizualizację postępu poprzez wykresy maksymalnego ciężaru dla każdego ćwiczenia
- Przeglądanie historii wszystkich sesji treningowych

### Stack technologiczny
- Backend: Symfony (PHP)
- Frontend: Next.js (React, TypeScript)
- Baza danych: PostgreSQL
- Deployment: Aplikacja webowa, wyłącznie online

### Grupa docelowa
Aplikacja skierowana jest do dwóch głównych person:
1. Początkujący użytkownicy siłowni - potrzebujący prostego narzędzia do budowania nawyku systematycznego śledzenia treningów
2. Średniozaawansowani użytkownicy - wymagający precyzyjnego śledzenia objętości treningowej i analizy postępu

### Język interfejsu
Polski jako główny język interfejsu z przygotowaniem architektury pod internacjonalizację (i18n). Słownik ćwiczeń dostępny w języku polskim i angielskim od startu.

## 2. Problem użytkownika

### Główny problem
Osoby trenujące na siłowni napotykają trudności w efektywnym śledzeniu swoich postępów. Tradycyjne metody są niewystarczające:

- Notatniki papierowe: niewygodne w warunkach siłowni, brak wizualizacji postępu, łatwo je zgubić
- Arkusze kalkulacyjne: trudne w obsłudze na telefonie, czasochłonne w aktualizacji, brak intuicyjnych wykresów
- Brak narzędzia: uniemożliwia obiektywną ocenę postępu, prowadzi do powtarzania tych samych ciężarów

### Konsekwencje problemu
- Brak motywacji do dalszych treningów z powodu niewidocznego postępu
- Trudność w planowaniu progresji obciążenia
- Utrata czasu na zastanawianie się, jakie ciężary użyć podczas następnej sesji
- Niemożność identyfikacji słabych punktów treningowych

### Oczekiwane rozwiązanie
Użytkownicy potrzebują prostego, mobilnego narzędzia, które:
- Pozwala szybko zapisać sesję treningową bezpośrednio na siłowni (w mniej niż 5 minut)
- Automatycznie wizualizuje postęp bez dodatkowej konfiguracji
- Przechowuje kompletną historię treningów w jednym miejscu
- Działa sprawnie na urządzeniach mobilnych

## 3. Wymagania funkcjonalne

### 3.1 System autoryzacji i zarządzania kontem użytkownika

#### Rejestracja
- Formularz rejestracji z polami: email, hasło, potwierdzenie hasła
- Walidacja wymagań hasła: minimum 8 znaków, 1 wielka litera, 1 cyfra
- Hashowanie haseł algorytmem bcrypt lub argon2
- Brak weryfikacji email w wersji MVP
- Walidacja unikalności adresu email

#### Logowanie
- Formularz logowania: email i hasło
- Sesja użytkownika po pomyślnym zalogowaniu
- Wylogowanie z opcją dostępną w sekcji Profil

#### Bezpieczeństwo
- Ochrona przed SQL injection
- Ochrona przed atakami XSS
- HTTPS w środowisku produkcyjnym
- Brak funkcji resetowania hasła w MVP
- Brak integracji z OAuth w MVP

### 3.2 Słownik ćwiczeń

#### Struktura słownika
- 50-70 predefiniowanych ćwiczeń oporowych
- 6 kategorii mięśniowych:
  - Klatka piersiowa (8-12 ćwiczeń)
  - Plecy (8-12 ćwiczeń)
  - Nogi (8-12 ćwiczeń)
  - Barki (8-12 ćwiczeń)
  - Biceps (8-12 ćwiczeń)
  - Triceps (8-12 ćwiczeń)

#### Zawartość słownika
- Najpopularniejsze ćwiczenia podstawowe: przysiad, martwy ciąg, wyciskanie sztangi leżąc, podciąganie
- Nazwy ćwiczeń w języku polskim i angielskim
- Przypisanie kategorii dla każdego ćwiczenia
- Brak możliwości dodawania własnych ćwiczeń przez użytkownika w MVP

#### Wyświetlanie
- Lista ćwiczeń podzielona na kategorie lub wyszukiwanie
- Możliwość filtrowania po kategorii podczas dodawania ćwiczenia do sesji

### 3.3 Zarządzanie sesjami treningowymi

#### Tworzenie sesji
- Przycisk "Nowa sesja" prominentnie wyświetlony na Dashboardzie
- Przekierowanie na dedykowaną stronę tworzenia sesji
- Formularz metadanych sesji:
  - Data (automatycznie ustawiana na bieżącą)
  - Nazwa sesji (pole opcjonalne, np. "Trening A", "FBW")
  - Notatki (pole tekstowe opcjonalne dla dodatkowych uwag)

#### Dodawanie ćwiczeń do sesji
- Maksymalnie 15 ćwiczeń na sesję
- Wybór ćwiczenia z predefiniowanego słownika
- Możliwość dodania tego samego ćwiczenia wielokrotnie w jednej sesji

#### Dodawanie serii do ćwiczenia
- Dynamiczny formularz z przyciskiem "+ Dodaj serię"
- Każde kliknięcie dodaje nowy wiersz formularza
- Pola dla każdej serii:
  - Numer serii (automatyczny, sekwencyjny)
  - Liczba powtórzeń (zakres: 1-100)
  - Ciężar w kilogramach (zakres: 0-500kg)
- Maksymalnie 20 serii na jedno ćwiczenie
- Możliwość usunięcia pojedynczej serii

#### Walidacje
- Jednostka miary: wyłącznie kilogramy
- Maksymalny ciężar: 500kg
- Maksymalne powtórzenia: 100
- Walidacja po stronie frontend i backend
- Komunikaty błędów w języku polskim

#### Edycja i usuwanie
- Pełna możliwość edycji sesji bez ograniczeń czasowych
- Edycja metadanych sesji (nazwa, data, notatki)
- Edycja ćwiczeń i serii w ramach sesji
- Usuwanie całej sesji z obowiązkowym potwierdzeniem
- Usuwanie pojedynczych ćwiczeń z sesji
- Usuwanie pojedynczych serii

### 3.4 Dashboard i nawigacja

#### Ekran główny (Dashboard)
Po zalogowaniu użytkownik widzi:
- Przycisk "Nowa sesja" (duży, prominentny, łatwo dostępny)
- Panel statystyk:
  - Select/dropdown do wyboru ćwiczenia
  - Wykres liniowy postępu wybranego ćwiczenia
- Lista ostatnich 5 sesji treningowych z podstawowymi informacjami (data, nazwa, liczba ćwiczeń)

#### Bottom bar navigation (mobile)
Nawigacja dostępna na dole ekranu:
- Dashboard (ikona + label)
- Historia (ikona + label)
- Profil (ikona + label)

#### Responsywność
- Mobile-first design
- Optymalizacja dla ekranów dotykowych
- Płynne animacje (cel: 60fps)
- Czytelne przyciski i pola formularzy na małych ekranach

### 3.5 Statystyki i wizualizacja postępu

#### Panel statystyk
- Umieszczony na Dashboardzie
- Select/dropdown z listą wszystkich ćwiczeń ze słownika
- Wykres liniowy po wybraniu ćwiczenia:
  - Oś X: data sesji treningowej
  - Oś Y: maksymalny ciężar (w kg)
  - Punkty danych reprezentują maksymalny ciężar z danej sesji

#### Algorytm obliczania maksymalnego ciężaru
- Dla danego ćwiczenia w danej sesji: maksymalny ciężar użyty w dowolnej serii
- Przykład: jeśli wykonano 1x8@20kg, 1x8@40kg, 3x10@70kg, to max ciężar = 70kg
- Brak zaawansowanych obliczeń (1RM) w MVP

#### Wyświetlanie danych
- Jeśli użytkownik nie wykonał jeszcze wybranego ćwiczenia: komunikat informacyjny
- Jeśli wykonano tylko raz: wyświetlenie pojedynczego punktu
- Tooltips przy najechaniu/dotknięciu punktu (data i wartość)

### 3.6 Historia treningów

#### Strona Historia
- Lista wszystkich sesji treningowych użytkownika
- Sortowanie: najnowsze sesje na górze
- Informacje wyświetlane dla każdej sesji:
  - Data sesji
  - Nazwa sesji (jeśli podana)
  - Liczba wykonanych ćwiczeń
  - Przycisk/opcja rozwinięcia szczegółów

#### Szczegóły sesji
- Po rozwinięciu/kliknięciu wyświetlenie:
  - Wszystkich ćwiczeń w sesji
  - Wszystkich serii każdego ćwiczenia (powtórzenia x ciężar)
  - Notatek (jeśli dodano)
- Opcje: Edytuj, Usuń

#### Filtrowanie
- Filtr czasowy z predefiniowanymi opcjami:
  - Ostatnie 7 dni
  - Ostatnie 30 dni
  - Ostatnie 90 dni
- Brak zaawansowanego wyszukiwania w MVP

### 3.7 Profil użytkownika

#### Informacje w profilu
- Wyświetlenie adresu email
- Data rejestracji
- Przycisk wylogowania

#### Brak w MVP
- Zmiana hasła
- Usunięcie konta
- Edycja danych osobowych (poza email)
- Zdjęcie profilowe

## 4. Granice produktu

### Co WCHODZI w zakres MVP
- Prosty system kont użytkowników (email + hasło)
- Predefiniowany słownik 50-70 ćwiczeń oporowych
- Tworzenie sesji treningowych z metadanymi
- Dodawanie ćwiczeń i serii do sesji
- Edycja i usuwanie sesji, ćwiczeń, serii
- Statystyki maksymalnego ciężaru z wizualizacją
- Historia wszystkich sesji z podstawowym filtrowaniem
- Responsywna aplikacja webowa (mobile-first)
- Polski interfejs z przygotowaniem pod i18n
- Działanie wyłącznie online

### Co NIE WCHODZI w zakres MVP
- Złożone systemy kont użytkowników:
  - OAuth (Google, Facebook)
  - Weryfikacja email
  - Resetowanie hasła
  - Dwuskładnikowe uwierzytelnianie (2FA)
- Zaawansowane funkcje treningowe:
  - Szablony treningów
  - Sugestie treningów na podstawie historii
  - Obliczenia 1RM (one-rep max)
  - Plany treningowe
  - Periodyzacja
- Śledzenie dodatkowych metryk:
  - Masa ciała użytkownika
  - Pomiary ciała
  - Czas trwania sesji (automatyczny)
  - Tempo wykonania serii
  - Czas odpoczynku między seriami
- Funkcje społecznościowe:
  - Udostępnianie treningów
  - Obserwowanie innych użytkowników
  - Ranking/leaderboard
- Zaawansowana analityka:
  - Objętość treningowa (volume)
  - Porównanie tygodni/miesięcy
  - Zaawansowane wykresy i raporty
  - Eksport danych
- Dodatkowe funkcjonalności:
  - Własne ćwiczenia użytkownika
  - Tryb offline
  - Natywne aplikacje mobilne (iOS/Android)
  - Powiadomienia push
  - Integracje z innymi aplikacjami
  - Kalkulator kalorii/makroskładników

### Ograniczenia techniczne MVP
- Brak trybu offline (wymagane stałe połączenie internetowe)
- Brak automatycznej synchronizacji między urządzeniami (wymaga odświeżenia)
- Podstawowa walidacja danych (bez zaawansowanej detekcji anomalii)

## 5. Historyjki użytkowników

### 5.1 Autentykacja i zarządzanie kontem

#### US-001: Rejestracja nowego konta
Jako nowy użytkownik chcę zarejestrować konto przy użyciu adresu email i hasła, aby móc korzystać z aplikacji.

Kryteria akceptacji:
- Formularz rejestracji zawiera pola: email, hasło, potwierdzenie hasła
- System waliduje poprawność formatu adresu email
- System wymaga hasła o długości minimum 8 znaków, zawierającego co najmniej 1 wielką literę i 1 cyfrę
- System waliduje, czy hasło i potwierdzenie hasła są identyczne
- System sprawdza unikalność adresu email (komunikat błędu jeśli email już istnieje)
- Hasło jest hashowane przed zapisem w bazie danych (bcrypt/argon2)
- Po pomyślnej rejestracji użytkownik jest automatycznie zalogowany i przekierowany na Dashboard
- System wyświetla odpowiednie komunikaty błędów w języku polskim

#### US-002: Logowanie do aplikacji
Jako zarejestrowany użytkownik chcę zalogować się do aplikacji przy użyciu emaila i hasła, aby uzyskać dostęp do moich danych treningowych.

Kryteria akceptacji:
- Formularz logowania zawiera pola: email i hasło
- System weryfikuje poprawność credentials
- Po pomyślnym logowaniu użytkownik jest przekierowany na Dashboard
- System wyświetla komunikat błędu przy niepoprawnych danych logowania
- System tworzy bezpieczną sesję użytkownika
- Brak możliwości dostępu do chronionych zasobów bez zalogowania

#### US-003: Wylogowanie z aplikacji
Jako zalogowany użytkownik chcę mieć możliwość wylogowania się z aplikacji, aby zabezpieczyć moje dane na urządzeniu współdzielonym.

Kryteria akceptacji:
- Przycisk/opcja wylogowania dostępna w sekcji Profil
- Po kliknięciu wylogowania sesja użytkownika jest zakończona
- Użytkownik jest przekierowany na stronę logowania
- Brak możliwości dostępu do chronionych zasobów po wylogowaniu
- Próba dostępu do chronionych stron przekierowuje na logowanie

#### US-004: Walidacja bezpieczeństwa hasła
Jako użytkownik rejestrujący konto chcę otrzymywać informacje zwrotne o sile mojego hasła w czasie rzeczywistym, aby utworzyć bezpieczne hasło.

Kryteria akceptacji:
- Podczas wpisywania hasła wyświetlane są wymagania: min. 8 znaków, 1 wielka litera, 1 cyfra
- Wizualna informacja zwrotna (checkmarki/kolory) dla spełnionych wymagań
- Niemożność wysłania formularza przy niespełnieniu wymagań
- Komunikaty błędów w języku polskim

### 5.2 Zarządzanie sesjami treningowymi

#### US-005: Tworzenie nowej sesji treningowej
Jako użytkownik chcę rozpocząć nową sesję treningową z telefonu na siłowni, aby na bieżąco rejestrować mój trening.

Kryteria akceptacji:
- Przycisk "Nowa sesja" jest widoczny i prominentny na Dashboardzie
- Kliknięcie przycisku przekierowuje na dedykowaną stronę tworzenia sesji
- Data sesji jest automatycznie ustawiana na bieżącą datę
- Możliwość dodania opcjonalnej nazwy sesji (pole tekstowe)
- Możliwość dodania opcjonalnych notatek (pole tekstowe wieloliniowe)
- Strona jest responsywna i działa sprawnie na urządzeniach mobilnych

#### US-006: Dodawanie ćwiczenia do sesji
Jako użytkownik tworzący sesję chcę dodać ćwiczenie z listy dostępnych ćwiczeń, aby zapisać wykonane ćwiczenie.

Kryteria akceptacji:
- Dostępna lista/select wszystkich ćwiczeń ze słownika
- Możliwość filtrowania ćwiczeń po kategorii mięśniowej
- Możliwość wyszukiwania ćwiczenia po nazwie
- Po wybraniu ćwiczenia pojawia się w sesji z możliwością dodawania serii
- Możliwość dodania tego samego ćwiczenia wielokrotnie w jednej sesji
- Maksymalnie 15 ćwiczeń w jednej sesji (komunikat przy osiągnięciu limitu)
- Nazwy ćwiczeń wyświetlane w języku interfejsu (polski)

#### US-007: Dodawanie serii do ćwiczenia
Jako użytkownik chcę szybko dodać wiele serii do ćwiczenia podczas treningu, aby precyzyjnie zapisać wszystkie wykonane serie.

Kryteria akceptacji:
- Przycisk "+ Dodaj serię" dostępny dla każdego ćwiczenia
- Kliknięcie przycisku dodaje nowy wiersz formularza serii
- Numer serii jest automatycznie generowany sekwencyjnie (1, 2, 3...)
- Pola do wprowadzenia: liczba powtórzeń (1-100), ciężar w kg (0-500)
- Możliwość dodania maksymalnie 20 serii na jedno ćwiczenie
- Walidacja danych po stronie frontend (komunikaty błędów w czasie rzeczywistym)
- Możliwość usunięcia pojedynczej serii (przycisk X/ikona usuwania)

#### US-008: Zapisanie sesji treningowej
Jako użytkownik chcę zapisać ukończoną sesję treningową, aby dane były zachowane w systemie.

Kryteria akceptacji:
- Przycisk "Zapisz sesję" dostępny na stronie tworzenia sesji
- Walidacja po stronie backend przed zapisem
- Po zapisie użytkownik jest przekierowany na Dashboard
- Sesja pojawia się w liście ostatnich 5 sesji na Dashboardzie
- Sesja pojawia się w Historii treningów
- Komunikat potwierdzenia pomyślnego zapisu

#### US-009: Edycja istniejącej sesji
Jako użytkownik chcę edytować wcześniej zapisaną sesję, aby poprawić błędy lub dodać zapomniane informacje.

Kryteria akceptacji:
- Opcja "Edytuj" dostępna dla każdej sesji (Dashboard, Historia)
- Możliwość edycji metadanych: nazwa, data, notatki
- Możliwość dodania nowych ćwiczeń do sesji
- Możliwość edycji istniejących ćwiczeń i serii
- Możliwość usunięcia ćwiczeń i serii
- Brak ograniczeń czasowych na edycję
- Walidacja danych przy zapisie zmian
- Komunikat potwierdzenia zapisanych zmian

#### US-010: Usuwanie sesji treningowej
Jako użytkownik chcę usunąć sesję treningową, której już nie potrzebuję, aby utrzymać porządek w danych.

Kryteria akceptacji:
- Opcja "Usuń" dostępna dla każdej sesji (Dashboard, Historia)
- Przed usunięciem wyświetlane jest okno potwierdzenia z pytaniem
- Możliwość anulowania akcji usunięcia
- Po potwierdzeniu sesja jest trwale usuwana z bazy danych
- Usunięcie sesji usuwa wszystkie powiązane ćwiczenia i serie
- Sesja znika z Dashboardu i Historii
- Dane statystyczne są aktualizowane (wykres)
- Komunikat potwierdzenia usunięcia

### 5.3 Słownik ćwiczeń

#### US-011: Przeglądanie słownika ćwiczeń
Jako użytkownik chcę przeglądać dostępne ćwiczenia pogrupowane według kategorii mięśniowych, aby łatwo znaleźć interesujące mnie ćwiczenie.

Kryteria akceptacji:
- Słownik zawiera 50-70 predefiniowanych ćwiczeń
- Ćwiczenia pogrupowane w 6 kategorii: klatka piersiowa, plecy, nogi, barki, biceps, triceps
- Każda kategoria zawiera 8-12 ćwiczeń
- Możliwość przeglądania według kategorii
- Nazwy ćwiczeń w języku polskim
- Lista czytelna na urządzeniach mobilnych

#### US-012: Wyszukiwanie ćwiczenia
Jako użytkownik chcę wyszukać konkretne ćwiczenie po nazwie, aby szybko je znaleźć bez przeglądania wszystkich kategorii.

Kryteria akceptacji:
- Pole wyszukiwania dostępne przy wyborze ćwiczenia w sesji
- Wyszukiwanie działa w czasie rzeczywistym (na bieżąco)
- Wyniki wyszukiwania uwzględniają częściowe dopasowanie nazwy
- Wyszukiwanie rozróżnia polskie znaki diakrytyczne
- Brak wyników wyświetla odpowiedni komunikat

#### US-013: Wyświetlanie ćwiczeń w języku interfejsu
Jako użytkownik korzystający z polskiego interfejsu chcę widzieć nazwy ćwiczeń po polsku, aby lepiej zrozumieć ich znaczenie.

Kryteria akceptacji:
- Każde ćwiczenie ma nazwę w języku polskim i angielskim w bazie danych
- System wyświetla nazwy ćwiczeń zgodnie z wybranym językiem interfejsu
- Domyślny język: polski
- Przygotowanie architektury pod przyszłe dodanie innych języków (i18n)

### 5.4 Statystyki i wizualizacja postępu

#### US-014: Wyświetlanie wykresu postępu dla wybranego ćwiczenia
Jako użytkownik chcę zobaczyć wykres mojego postępu w wybranym ćwiczeniu, aby zmotywować się do dalszych treningów.

Kryteria akceptacji:
- Panel statystyk widoczny na Dashboardzie
- Dropdown/select z listą wszystkich ćwiczeń ze słownika
- Po wybraniu ćwiczenia wyświetla się wykres liniowy
- Oś X wykresu: daty sesji treningowych
- Oś Y wykresu: maksymalny ciężar w kg
- Każdy punkt na wykresie reprezentuje maksymalny ciężar z danej sesji
- Tooltips pokazują datę i wartość przy najechaniu/dotknięciu punktu
- Wykres jest responsywny i czytelny na mobile

#### US-015: Obliczanie maksymalnego ciężaru dla ćwiczenia
Jako użytkownik chcę, aby system automatycznie obliczał mój maksymalny ciężar dla każdego ćwiczenia, aby nie musieć tego robić ręcznie.

Kryteria akceptacji:
- Dla danego ćwiczenia w danej sesji system wybiera maksymalny ciężar ze wszystkich serii
- Przykład: serie 1x8@20kg, 1x8@40kg, 3x10@70kg → max = 70kg
- Obliczenie wykonywane automatycznie przy każdym dodaniu/edycji sesji
- Dane na wykresie aktualizują się natychmiast po zapisie sesji

#### US-016: Wyświetlanie komunikatu przy braku danych
Jako użytkownik chcę zobaczyć informacyjny komunikat, gdy wybieram ćwiczenie, którego jeszcze nie wykonywałem, aby wiedzieć dlaczego wykres jest pusty.

Kryteria akceptacji:
- Gdy wybrane ćwiczenie nie występuje w żadnej sesji, wyświetlany jest komunikat
- Komunikat w języku polskim: "Nie wykonano jeszcze tego ćwiczenia. Dodaj pierwszą sesję!"
- Brak wykresu przy braku danych
- Po dodaniu pierwszej sesji z tym ćwiczeniem wykres się pojawia

### 5.5 Dashboard i nawigacja

#### US-017: Wyświetlanie dashboardu po zalogowaniu
Jako zalogowany użytkownik chcę zobaczyć dashboard jako główny ekran aplikacji, aby mieć szybki dostęp do kluczowych funkcji.

Kryteria akceptacji:
- Dashboard jest pierwszą stroną po zalogowaniu
- Dashboard zawiera:
  - Przycisk "Nowa sesja" (duży, wyraźny)
  - Panel statystyk (select ćwiczenia + wykres)
  - Lista ostatnich 5 sesji treningowych
- Układ responsywny dostosowany do urządzeń mobilnych
- Szybkie ładowanie dashboardu (poniżej 2 sekund)

#### US-018: Przeglądanie ostatnich sesji na dashboardzie
Jako użytkownik chcę widzieć moje ostatnie 5 sesji treningowych na dashboardzie, aby szybko przypomnieć sobie co trenowałem.

Kryteria akceptacji:
- Lista ostatnich 5 sesji widoczna na Dashboardzie
- Dla każdej sesji wyświetlane: data, nazwa (jeśli podana), liczba ćwiczeń
- Sesje posortowane od najnowszej do najstarszej
- Możliwość kliknięcia sesji aby zobaczyć szczegóły
- Jeśli użytkownik ma mniej niż 5 sesji, wyświetlane są wszystkie
- Jeśli brak sesji: komunikat zachęcający do dodania pierwszej

#### US-019: Nawigacja między głównymi sekcjami aplikacji
Jako użytkownik chcę łatwo nawigować między głównymi sekcjami aplikacji z poziomu telefonu, aby szybko przechodzić między funkcjami.

Kryteria akceptacji:
- Bottom bar navigation widoczny na dole ekranu (mobile)
- Trzy główne sekcje: Dashboard, Historia, Profil
- Każda sekcja ma ikonę i label tekstowy
- Aktywna sekcja jest wizualnie wyróżniona
- Kliknięcie przekierowuje do odpowiedniej sekcji
- Nawigacja jest sticky (zawsze widoczna)
- Responsywne działanie na desktop (alternatywna forma nawigacji)

### 5.6 Historia treningów

#### US-020: Przeglądanie pełnej historii sesji
Jako użytkownik chcę zobaczyć wszystkie moje sesje treningowe w jednym miejscu, aby mieć pełny przegląd mojej historii treningów.

Kryteria akceptacji:
- Strona "Historia" dostępna z bottom bar navigation
- Wyświetlana lista wszystkich sesji użytkownika
- Sesje posortowane od najnowszej do najstarszej
- Dla każdej sesji wyświetlane: data, nazwa (jeśli podana), liczba ćwiczeń
- Infinite scroll lub paginacja przy dużej liczbie sesji
- Szybkie ładowanie listy

#### US-021: Rozwijanie szczegółów sesji w historii
Jako użytkownik chcę rozwinąć szczegóły sesji bez przechodzenia na osobną stronę, aby szybko sprawdzić co trenowałem.

Kryteria akceptacji:
- Możliwość kliknięcia/dotknięcia sesji aby rozwinąć szczegóły
- Rozwinięcie pokazuje:
  - Wszystkie ćwiczenia w sesji
  - Wszystkie serie każdego ćwiczenia (format: powtórzenia x ciężar)
  - Notatki (jeśli dodano)
- Możliwość zwinięcia szczegółów ponownym kliknięciem
- Opcje: Edytuj, Usuń (dostępne w rozwiniętym widoku)

#### US-022: Filtrowanie historii po dacie
Jako użytkownik chcę filtrować historię treningów według przedziałów czasowych, aby skupić się na określonym okresie.

Kryteria akceptacji:
- Dostępne filtry czasowe: ostatnie 7 dni, ostatnie 30 dni, ostatnie 90 dni
- Możliwość wybrania jednego filtru na raz
- Lista sesji aktualizuje się po wybraniu filtru
- Domyślnie wyświetlane wszystkie sesje
- Licznik sesji spełniających kryteria filtru
- Komunikat jeśli brak sesji w wybranym okresie

### 5.7 Profil użytkownika

#### US-023: Wyświetlanie informacji profilu
Jako użytkownik chcę zobaczyć podstawowe informacje o moim koncie, aby wiedzieć jakie dane są zapisane.

Kryteria akceptacji:
- Strona "Profil" dostępna z bottom bar navigation
- Wyświetlany adres email użytkownika
- Wyświetlana data rejestracji konta
- Przycisk "Wyloguj" widoczny i dostępny
- Układ responsywny

#### US-024: Zmiana języka interfejsu (przygotowanie)
Jako użytkownik chcę mieć możliwość zmiany języka interfejsu w przyszłości, dlatego aplikacja powinna być przygotowana na internacjonalizację.

Kryteria akceptacji:
- Architektura aplikacji wykorzystuje system i18n
- Wszystkie teksty interfejsu przechowywane w plikach tłumaczeń
- Słownik ćwiczeń zawiera nazwy w języku polskim i angielskim
- Domyślny język: polski
- Kod przygotowany na dodanie selectora języka w przyszłości (poza MVP)

### 5.8 Walidacje i obsługa błędów

#### US-025: Walidacja danych wejściowych w formularzach
Jako użytkownik chcę otrzymywać jasne komunikaty o błędach walidacji, aby poprawić nieprawidłowe dane.

Kryteria akceptacji:
- Walidacja po stronie frontend (w czasie rzeczywistym gdzie możliwe)
- Walidacja po stronie backend (przed zapisem do bazy)
- Komunikaty błędów w języku polskim
- Zakresy walidacji:
  - Powtórzenia: 1-100
  - Ciężar: 0-500 kg
  - Ćwiczenia na sesję: max 15
  - Serie na ćwiczenie: max 20
- Vizualne wskazanie pól z błędami
- Niemożność wysłania formularza z błędami walidacji

#### US-026: Obsługa błędów sieciowych
Jako użytkownik chcę być informowany o problemach z połączeniem, aby wiedzieć że dane mogą nie być zapisane.

Kryteria akceptacji:
- Komunikat błędu przy braku połączenia internetowego
- Komunikat błędu przy timeout requestu
- Komunikat błędu przy błędzie serwera (500)
- Możliwość ponowienia akcji po ustąpieniu błędu
- Komunikaty w języku polskim, zrozumiałe dla użytkownika

#### US-027: Ochrona przed utratą danych
Jako użytkownik chcę mieć pewność, że dane wprowadzone w formularzach nie zostaną utracone przy przypadkowym zamknięciu, aby nie tracić czasu na ponowne wprowadzanie.

Kryteria akceptacji:
- Potwierdzenie przed opuszczeniem strony z niezapisanymi zmianami
- Komunikat: "Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?"
- Możliwość anulowania i powrotu do formularza
- Dotyczy: tworzenie sesji, edycja sesji

### 5.9 Scenariusze skrajne i alternatywne

#### US-028: Korzystanie z aplikacji przy pierwszym logowaniu (brak danych)
Jako nowy użytkownik logujący się po raz pierwszy chcę zobaczyć pomocne komunikaty i wskazówki, aby wiedzieć jak zacząć korzystać z aplikacji.

Kryteria akceptacji:
- Dashboard przy braku sesji wyświetla komunikat powitalny
- Zachęta do utworzenia pierwszej sesji treningowej
- Panel statystyk wyświetla komunikat "Dodaj pierwszą sesję aby zobaczyć statystyki"
- Lista ostatnich sesji wyświetla "Brak sesji. Zacznij od dodania pierwszej!"
- Historia wyświetla komunikat "Nie masz jeszcze żadnych sesji treningowych"

#### US-029: Dodanie sesji z jednym ćwiczeniem i jedną serią (minimalna sesja)
Jako użytkownik chcę móc zapisać sesję z minimalną ilością danych, aby aplikacja była elastyczna.

Kryteria akceptacji:
- Możliwość utworzenia sesji z tylko 1 ćwiczeniem
- Możliwość dodania tylko 1 serii do ćwiczenia
- Brak wymuszania minimalnej liczby ćwiczeń czy serii
- Sesja zapisuje się poprawnie
- Sesja pojawia się w historii i na dashboardzie
- Dane uwzględniane w statystykach

#### US-030: Dodanie maksymalnej liczby ćwiczeń i serii
Jako zaawansowany użytkownik wykonujący obszerny trening chcę móc dodać maksymalną liczbę ćwiczeń i serii, aby zapisać całą sesję.

Kryteria akceptacji:
- Możliwość dodania 15 ćwiczeń do sesji (maksimum)
- Możliwość dodania 20 serii do każdego ćwiczenia (maksimum)
- Komunikat informacyjny przy osiągnięciu limitu ćwiczeń
- Komunikat informacyjny przy osiągnięciu limitu serii
- Brak możliwości przekroczenia limitów
- Formularz pozostaje czytelny i wydajny przy maksymalnej liczbie danych

#### US-031: Edycja sesji z przeszłości (np. sprzed roku)
Jako użytkownik chcę móc edytować bardzo stare sesje, aby poprawić historyczne błędy w danych.

Kryteria akceptacji:
- Brak ograniczeń czasowych na edycję sesji
- Możliwość edycji sesji z dowolnej daty w przeszłości
- Możliwość zmiany daty sesji
- Wszystkie funkcje edycji działają identycznie jak dla nowych sesji
- Statystyki aktualizują się po edycji historycznych danych

#### US-032: Usunięcie wszystkich danych użytkownika
Jako użytkownik chcę mieć możliwość usunięcia wszystkich moich sesji, aby zacząć od nowa (użytkownik usuwa ręcznie wszystkie sesje).

Kryteria akceptacji:
- Możliwość usunięcia każdej sesji pojedynczo z potwierdzeniem
- Po usunięciu wszystkich sesji aplikacja wyświetla komunikaty dla pustego stanu
- Dashboard pokazuje zachętę do dodania pierwszej sesji
- Statystyki wyświetlają komunikat o braku danych
- Historia wyświetla komunikat o braku sesji
- Konto użytkownika pozostaje aktywne

#### US-033: Równoczesne dodawanie tego samego ćwiczenia w sesji
Jako użytkownik chcę dodać to samo ćwiczenie kilka razy w jednej sesji (np. pause squats i regular squats jako osobne wpisy), aby móc to rozróżnić w notatkach.

Kryteria akceptacji:
- Możliwość dodania tego samego ćwiczenia wielokrotnie w jednej sesji
- Każde wystąpienie traktowane jako osobny wpis
- Możliwość dodania różnych notatek do każdego wystąpienia (poprzez notatki w sesji)
- Wszystkie serie wszystkich wystąpień uwzględniane w statystykach (maksymalny ciężar)

#### US-034: Wprowadzenie wartości granicznych (0kg, 500kg, 1 powtórzenie, 100 powtórzeń)
Jako użytkownik chcę móc wprowadzić wartości graniczne, aby system poprawnie obsługiwał skrajne przypadki.

Kryteria akceptacji:
- Możliwość wprowadzenia 0 kg (ciężar własnego ciała)
- Możliwość wprowadzenia 500 kg (maksymalny limit)
- Komunikat błędu przy próbie przekroczenia 500 kg
- Możliwość wprowadzenia 1 powtórzenia (test 1RM)
- Możliwość wprowadzenia 100 powtórzeń (maksymalny limit)
- Komunikat błędu przy próbie przekroczenia 100 powtórzeń
- Walidacja zapobiega wprowadzeniu wartości ujemnych

## 6. Metryki sukcesu

### 6.1 Główna metryka MVP

Liczba stworzonych sesji treningowych

Definicja: Całkowita liczba sesji treningowych zapisanych przez wszystkich użytkowników aplikacji.

### 6.2 Cele ilościowe (3 miesiące od startu)

#### Metryki użytkowników
- 100 aktywnych użytkowników
- 40% retencja tygodniowa (użytkownicy wracający co najmniej raz w tygodniu)
- Średnio 3 sesje treningowe na użytkownika tygodniowo
- Minimum 1000 sesji treningowych stworzonych łącznie

#### Metryki zaangażowania
- Średni czas dodawania pełnej sesji (5 ćwiczeń): poniżej 5 minut
- Minimum 60% użytkowników dodaje sesję w ciągu pierwszych 24h od rejestracji
- Średnio 8 ćwiczeń w sesji
- Minimum 70% użytkowników korzysta z funkcji statystyk (sprawdza wykresy)

### 6.3 Kryteria sukcesu funkcjonalnego

Wszystkie wymienione funkcjonalności muszą działać poprawnie:

#### US-001 do US-004: System kont użytkowników
- Rejestracja z walidacją email i hasła
- Logowanie z weryfikacją credentials
- Bezpieczne hashowanie haseł
- Wylogowanie i zarządzanie sesją

#### US-005 do US-010, US-029 do US-034: Dodawanie i zarządzanie sesjami
- Tworzenie sesji z metadanymi
- Dodawanie ćwiczeń i serii
- Edycja sesji bez ograniczeń czasowych
- Usuwanie z potwierdzeniem
- Obsługa przypadków skrajnych (minimalne i maksymalne dane)

#### US-011 do US-013: Słownik ćwiczeń
- 50-70 ćwiczeń w 6 kategoriach
- Wyszukiwanie i filtrowanie
- Nazwy po polsku i angielsku

#### US-014 do US-016: Statystyki
- Wykres liniowy postępu dla wybranego ćwiczenia
- Automatyczne obliczanie maksymalnego ciężaru
- Obsługa braku danych

#### US-017 do US-019: Dashboard i nawigacja
- Dashboard z kluczowymi funkcjami
- Lista ostatnich 5 sesji
- Bottom bar navigation (mobile)

#### US-020 do US-022: Historia treningów
- Lista wszystkich sesji
- Rozwijane szczegóły
- Filtrowanie po dacie

#### US-023 do US-024: Profil
- Wyświetlanie danych konta
- Wylogowanie
- Przygotowanie pod i18n

#### US-025 do US-027: Walidacje i obsługa błędów
- Walidacja frontend i backend
- Obsługa błędów sieciowych
- Ochrona przed utratą danych

### 6.4 Miary jakości

#### Wydajność
- Czas ładowania Dashboardu: poniżej 2 sekund
- Czas zapisania sesji: poniżej 1 sekundy
- Płynność animacji na mobile: 60 FPS
- Responsywność formularzy: natychmiastowa informacja zwrotna

#### Użyteczność (UX)
- Użytkownik powinien dodać pierwszą sesję bez instrukcji
- Intuicyjny przepływ: rejestracja → dashboard → nowa sesja → zapis w mniej niż 10 minut
- Mobile-first: wszystkie funkcje w pełni użyteczne na telefonie
- Czytelność na małych ekranach (minimum 320px szerokości)

#### Bezpieczeństwo
- 100% haseł hashowanych (bcrypt/argon2)
- Brak podatności SQL injection (parametryzowane zapytania)
- Brak podatności XSS (sanityzacja inputów)
- HTTPS w środowisku produkcyjnym
- Bezpieczne zarządzanie sesjami

#### Niezawodność
- Dostępność aplikacji: 99% uptime
- Brak utraty danych przy zapisie sesji
- Poprawna walidacja zapobiegająca zapisowi nieprawidłowych danych
- Graceful error handling (przyjazne komunikaty błędów)

### 6.5 Sposób mierzenia sukcesu

#### Metryki techniczne (Analytics)
- Liczba rejestracji użytkowników
- Liczba utworzonych sesji (główna metryka)
- Liczba dodanych ćwiczeń i serii
- Częstotliwość korzystania (daily/weekly active users)
- Retencja: % użytkowników wracających po 7, 14, 30 dniach
- Czas spędzony w aplikacji
- Najczęściej używane ćwiczenia

#### Metryki UX
- Czas do pierwszej sesji (time to first session)
- Współczynnik ukończenia sesji (sesje rozpoczęte vs zapisane)
- Bounce rate na stronie rejestracji
- Użycie funkcji statystyk (% użytkowników sprawdzających wykresy)
- Częstotliwość edycji/usuwania sesji

#### Feedback użytkowników (poza MVP, ale planowane)
- Net Promoter Score (NPS)
- Ankiety satysfakcji
- Zgłoszenia błędów
- Feature requests

### 6.6 Definicja sukcesu MVP

MVP uznajemy za sukces gdy:

1. Wszystkie 34 historie użytkownika (US-001 do US-034) zostały zaimplementowane i przetestowane
2. Osiągnięto minimum 100 aktywnych użytkowników w ciągu 3 miesięcy
3. Utworzono minimum 1000 sesji treningowych
4. Retencja tygodniowa wynosi minimum 40%
5. Brak krytycznych bugów wpływających na podstawową funkcjonalność
6. Aplikacja działa sprawnie na urządzeniach mobilnych (główna platforma)
7. Średni czas dodawania sesji poniżej 5 minut

Spełnienie tych kryteriów potwierdzi, że produkt rozwiązuje rzeczywisty problem użytkowników i stanowi solidną podstawę do dalszego rozwoju.

