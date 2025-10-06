# Dokumentacja API - Swagger UI

Ten projekt zawiera zintegrowaną dokumentację API opartą o Swagger UI.

## Jak używać Swagger UI

### Uruchamianie

1. Uruchom projekt Docker:
   ```bash
   docker compose up --wait
   ```

2. Swagger UI będzie dostępne pod adresem:
   - **http://localhost:8081** - interfejs Swagger UI

### Edytowanie dokumentacji API

Dokumentacja API znajduje się w pliku:
```
docs/swagger.json
```

### Struktura pliku swagger.json

Plik zawiera:
- **Podstawowe informacje** - tytuł, opis, wersja API
- **Serwery** - adresy URL do testowania API
- **Endpointy** - szczegółowy opis wszystkich endpointów API
- **Schemas** - definicje modeli danych
- **Tagi** - grupowanie endpointów

### Przykładowe endpointy

Domyślnie dokumentacja zawiera przykładowe endpointy:

#### Health Check
- `GET /api/health` - sprawdzenie statusu aplikacji

#### Users
- `GET /api/users` - lista użytkowników (z paginacją)
- `POST /api/users` - utworzenie nowego użytkownika  
- `GET /api/users/{id}` - szczegóły użytkownika
- `PUT /api/users/{id}` - aktualizacja użytkownika
- `DELETE /api/users/{id}` - usunięcie użytkownika

### Jak dodać nowy endpoint

1. Otwórz plik `docs/swagger.json`
2. Dodaj nowy endpoint w sekcji `paths`:

```json
{
  "paths": {
    "/api/twoj-endpoint": {
      "get": {
        "summary": "Opis endpointu",
        "description": "Szczegółowy opis",
        "tags": ["Nazwa grupy"],
        "responses": {
          "200": {
            "description": "Sukces",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "message": {
                      "type": "string",
                      "example": "Sukces"
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}
```

3. Jeśli potrzebujesz nowych modeli danych, dodaj je w sekcji `components/schemas`
4. Zapisz plik - zmiany będą widoczne natychmiast w Swagger UI

### Testowanie API

W interfejsie Swagger UI możesz:
- Przeglądać dokumentację endpointów
- Testować endpointy bezpośrednio z przeglądarki
- Sprawdzać przykładowe odpowiedzi
- Generować kod dla różnych języków programowania

### Konfiguracja zaawansowana

#### Zmiana portu Swagger UI

Edytuj plik `compose.yaml`:
```yaml
swagger-ui:
  ports:
    - "TWOJ_PORT:8080"  # Zmień TWOJ_PORT na żądany numer
```

#### Dodanie autoryzacji

W pliku `swagger.json` dodaj sekcję `components/securitySchemes`:
```json
{
  "components": {
    "securitySchemes": {
      "bearerAuth": {
        "type": "http",
        "scheme": "bearer",
        "bearerFormat": "JWT"
      }
    }
  },
  "security": [
    {
      "bearerAuth": []
    }
  ]
}
```

#### Wiele plików dokumentacji

Możesz utworzyć osobne pliki JSON dla różnych wersji API:
- `docs/api-v1.json`
- `docs/api-v2.json`

I zmienić konfigurację w `compose.yaml`:
```yaml
swagger-ui:
  environment:
    URLS: '[{"url": "/docs/api-v1.json", "name": "API v1"}, {"url": "/docs/api-v2.json", "name": "API v2"}]'
```

### Przydatne linki

- [Specyfikacja OpenAPI 3.0](https://swagger.io/specification/)
- [Swagger Editor](https://editor.swagger.io/) - edytor online do walidacji dokumentacji
- [Swagger UI Configuration](https://swagger.io/docs/open-source-tools/swagger-ui/usage/configuration/)

## Rozwiązywanie problemów

### Swagger UI nie wyświetla dokumentacji

1. Sprawdź czy plik `docs/swagger.json` ma poprawną składnię JSON
2. Sprawdź logi kontenera: `docker compose logs swagger-ui`
3. Zweryfikuj czy volume mapping działa: `docker compose exec swagger-ui ls -la /docs`

### Błędy walidacji JSON

Użyj [JSON Linter](https://jsonlint.com/) do sprawdzenia składni pliku swagger.json.

### Problemy z CORS

Jeśli testujesz API z poziomu Swagger UI i masz problemy z CORS, dodaj odpowiednie nagłówki w swojej aplikacji Symfony.

