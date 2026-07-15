# Postgres — niezależny, współdzielony + backup

Ten Postgres jest **osobną usługą infrastrukturalną** (`docker/postgres/docker-compose.yml`),
niezależną od aplikacji. Jeden serwer, wiele baz — dziś `kanban_urzedowy`, w przyszłości
inne projekty (devbrain itd.). Aplikacje łączą się z nim po sieci `kanban_net`, po nazwie
kontenera `kanban_postgres`.

## Uruchomienie (raz)

```bash
docker network create kanban_net          # wspólna sieć DB, raz na maszynie
cd docker/postgres && docker compose up -d
docker compose ps                         # healthy
```

Postgres żyje własnym cyklem — restart aplikacji go nie rusza. Dane w woluminie
`postgres_kanban_pgdata` przeżywają restart i przebudowę kontenera.

## Backup

Zrzut całego serwera (wszystkie bazy + role), gzip, retencja 14 ostatnich:

```bash
bash docker/postgres/backup.sh
# -> docker/postgres/backups/postgres_RRRR-MM-DD_GG-MM.sql.gz
```

Backupy lądują w `docker/postgres/backups/` (poza repo — `.gitignore`).

### Harmonogram (codziennie)

Cron na hoście, np. o 3:00:

```cron
0 3 * * * cd /home/mariusz/PhpstormProjects/Kanban-mf && bash docker/postgres/backup.sh >> docker/postgres/backups/backup.log 2>&1
```

## Przywrócenie

```bash
gunzip -c docker/postgres/backups/postgres_RRRR-MM-DD_GG-MM.sql.gz \
  | docker exec -i kanban_postgres psql -U kanban -d postgres
```

`pg_dumpall` odtwarza całość (bazy i role). Do przywrócenia pojedynczej bazy trzeba
osobnego `pg_dump <baza>` — dopiszemy, gdy pojawi się druga baza na tym serwerze.

## Do dopracowania (gdy dojdą kolejne projekty)

- Kopia poza maszynę (rsync/scp na inny host albo S3) — teraz backup leży na tym samym dysku.
- Osobne bazy per projekt + osobni użytkownicy z ograniczonymi uprawnieniami (nie wszystko na
  superuserze `kanban`).
- Weryfikacja odtwarzalności: okresowy test restore na pustej bazie.
