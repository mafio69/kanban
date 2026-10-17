# Kanban spraw urzędowych

Samodzielna aplikacja do prowadzenia spraw urzędowych na tablicy Kanban.
Sprawa jest centralnym obiektem — powstaje z e-maila (etykieta Gmail)
albo ze skanu papierowego pisma (OCR), a kolejne pisma w tej samej
sprawie są do niej grupowane zamiast tworzyć nowe karty.

**Status:** w budowie — działa tablica (etap E1), synchronizacja Gmaila
i grupowanie maili w sprawy są zaplanowane w `docs/`.

## Stack

- PHP 8.2+, Slim 4, PHP-DI, phpdotenv
- PostgreSQL (osobny, współdzielony compose w `docker/postgres/`)
- Docker Compose: php-fpm + nginx
- SPA w przeglądarce, testy e2e w Playwright

## Jakość

- PHPUnit (Unit / Integration / Feature) + testy e2e
- PHPStan, PHP CS Fixer, GrumPHP
- Git hooks i GitHub Actions jako twarde bramki:
  - `secret-guard.sh` — blokuje sekrety w śledzonych plikach,
  - `check-trivial-asserts.sh` — blokuje trywialne asercje w testach,
  - zmiana `tests/**` wymaga `[allow-test-change]` w opisie commita
    (ochrona testów przed "dopasowaniem do kodu", także przez AI).

## Uruchomienie

```bash
docker network create kanban_net   # raz na maszynie
cp .env.example .env               # uzupełnij zmienne
docker compose up -d --build       # aplikacja na :8090
composer test
```

## Praca z AI

Projekt jest rozwijany z pomocą modeli AI na jasnych zasadach —
patrz `EXECUTOR_RULES.md`, `docs/model-pracy-ai.md`
i `docs/handoff-dla-modeli-ai.md`.
