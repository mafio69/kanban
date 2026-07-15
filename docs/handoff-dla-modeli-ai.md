# Handoff — praca dla modeli AI

Krótki przewodnik: jak wejść w to repo i zacząć bezpiecznie pracować. Nadzór i trudniejsze
części prowadzi Claude (Sonnet/Opus); zadania mechaniczne z gotowym kontraktem realizują
modele współpracujące (np. opencode). Podział ról: `docs/model-pracy-ai.md`.

## Stan na dziś

Gotowe i zielone:

- **E0 — fundament**: Slim + PHP-DI, PostgreSQL (niezależny, `docker/postgres/`), migracje
  (`bin/migrate`, tabela `sprawy`), docker (aplikacja w root `docker-compose.yml`), logowanie
  błędów do `var/log` / woluminu `/var/log/app`.
- **E1 — board (read-only)**: `GET /api/sprawy` (repozytorium na PDO, cienki kontroler),
  frontend 5 kolumn (`public/index.html`), testy: PHPUnit (unit/feature/integration) + e2e
  Playwright z losowym tokenem.

Reszta etapów: `docs/kanban-todo-import.md`.

## Zanim cokolwiek zrobisz — przeczytaj

1. `EXECUTOR_RULES.md` — zasady nadrzędne (sekrety w `.env`, cienkie kontrolery, SOLID/DRY,
   `vendor/` nietykalny, OCR jako jedyny wyjątek od „zero AI").
2. `docs/testy-protokol.md` — test-first, testy nie do oszukania, **`tests/**` tylko za
   podwójną zgodą użytkownika** (git hooki).
3. `docs/model-pracy-ai.md` — podział ról i „bez rozwiązań tymczasowych".

## Jak odpalić lokalnie

```bash
composer install
docker network create kanban_net                 # raz
cd docker/postgres && docker compose up -d        # baza
cd ../.. && docker compose up -d --build           # aplikacja
docker compose exec php-fpm php bin/migrate

DB_HOST=127.0.0.1 composer test                    # PHPUnit + bramka asercji
npm ci && npm run e2e                              # e2e Playwright
```

## Pętla pracy (dla modelu wykonującego zadanie)

1. Weź zadanie ze `docs/kanban-todo-import.md`.
2. Jeśli Claude napisał już test etapu (czerwony) — **implementuj do zielonego**, nic poza
   kontraktem.
3. Nie ruszaj `tests/**` (blokują git hooki + zdrowy rozsądek).
4. Styl: kontroler przyjmuje request i oddaje response; logika w serwisie; jedna
   odpowiedzialność; early return zamiast zagnieżdżonych if-ów.
5. `composer test` i `npm run e2e` mają być zielone przed „gotowe".

## Zadania gotowe do przekazania (mechaniczne, jasny kontrakt)

Każde: cienki kontroler + metoda w `SprawaRepository`, wynik JSON. Claude dopisze czerwony
test przed startem.

- **PATCH `/api/sprawy/{id}/archive`** — body `{archived: bool}` → ustawia `archived`, zwraca
  zaktualizowaną sprawę. Repo: `setArchived(int $id, bool $archived)`.
- **DELETE `/api/sprawy/{id}`** — usuwa wiersz z `sprawy` (nie rusza maila/pliku). Repo:
  `delete(int $id)`. 404 gdy nie ma.
- **PATCH `/api/sprawy/{id}`** — edycja pól z whitelisty (`subject`, `sender`, `notes`,
  `ocr_text`). Pole spoza whitelisty → 422. Repo: `updateFields(int $id, array $fields)`.
- **PATCH `/api/sprawy/{id}/move`** — body `{stage, position}` → przenosi kartę, przelicza
  `position`. To najtrudniejsze z tej czwórki (repozycjonowanie) — z dobrym testem do zrobienia.

## Zadania, które prowadzi Claude

Integracje zewnętrzne i logika bezpieczeństwa: **E2 Gmail / E7 OAuth**, **E3 OCR (ovh-ocr)**,
**EG grupowanie maili**. Za dużo ruchomych części i decyzji — tu prowadzi Claude, ewentualnie
oddaje kawałki po napisaniu testów.
