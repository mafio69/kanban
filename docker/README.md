# Docker — Kanban urzędowy

Dwa osobne compose: Postgres (idzie na serwer 192.168.1.172) i aplikacja (nginx + php-fpm).
Łączy je jedna wspólna sieć Dockera.

## 0. Raz: wspólna sieć

```bash
docker network create kanban_net
```

## 1. Postgres

Hasło jest w `docker/postgres/.env` (poza repo). AI ustawiło wstępne — **zmień je**, i wtedy
zmień też `DB_PASSWORD` w głównym `.env`, żeby się zgadzały.

```bash
cd docker/postgres
docker compose up -d
docker compose ps        # healthcheck powinien byc healthy
```

Baza i użytkownik (`kanban_urzedowy` / `kanban`) tworzą się automatycznie przy pierwszym starcie.

## 2. Aplikacja

```bash
cd docker/app
docker compose up -d --build
curl http://localhost:8090/api/health   # {"status":"ok"}
```

php-fpm łączy się z Postgresem po nazwie `kanban_postgres` w sieci `kanban_net`
(`DB_HOST=kanban_postgres` w głównym `.env`). Z hosta (np. `psql`, `bin/migrate` poza
kontenerem) użyj `DB_HOST=127.0.0.1`.

## Sekrety

Hasła tylko w plikach `.env` (`/.env` i `docker/postgres/.env`) — oba w `.gitignore`.
W repo są jedynie `.env.example` z pustymi placeholderami. Cloudflare Tunnel (`kanban.virral.mf`)
dochodzi w etapie wdrożenia (E6.4), na razie appka słucha na `localhost:8090`
(port 8080 był zajęty przez inną usługę na tej maszynie).
