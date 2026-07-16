# e2e — powtarzalne środowisko testowe

Zasada: **środowisko** (przeglądarki + PHP) jest współdzielone jako jeden obraz Dockera,
a **testy zostają w repo**. To leczy „u mnie działa" — każdy przebieg jest identyczny.

## Co jest czym

- `docker/e2e/Dockerfile` — źródło obrazu: oficjalny Playwright (przeglądarki w środku) + PHP 8.3.
- Obraz publikowany do repo **`mafio69/kanban-e2e`** (Docker Hub). Konsumenci go **pobierają**,
  nie budują u siebie.
- Maintainer publikuje: `docker login` → `bash scripts/e2e-image-publish.sh [tag]`.
- Testy (`tests/e2e/*`) montują się z repo do kontenera przy starcie — nie ma ich w obrazie.
- Kontener łączy się z niezależnym Postgresem po sieci `kanban_net` i celuje w bazę
  `kanban_urzedowy_test`.

## Uruchomienie

Wymagania: `kanban_postgres` działa (w sieci `kanban_net`), baza `kanban_urzedowy_test`
istnieje i jest zmigrowana.

```bash
bash scripts/e2e-docker.sh
```

Skrypt buduje obraz (jeśli trzeba) i odpala testy w kontenerze. Screenshoty lądują jak zwykle
w `e2e-historia/` (folder montowany z repo).

## Dwa sposoby, ta sama zawartość testów

- Szybko, lokalnie (host ma PHP + Node): `DB_HOST=127.0.0.1 npm run e2e`.
- Powtarzalnie (Docker): `bash scripts/e2e-docker.sh`.

`DB_HOST`/`DB_NAME` nie są wpisane na sztywno — z hosta domyślnie `127.0.0.1`, w kontenerze
`kanban_postgres`. Te same pliki testów, różne środowisko.

## Wersje

Tag obrazu Playwrighta (`v1.48.0-jammy`) powinien pasować do `@playwright/test` w
`package.json`. Jak bumpniesz jedno — bumpnij drugie.
