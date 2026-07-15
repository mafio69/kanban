#!/usr/bin/env bash
# Backup calego serwera Postgres (wszystkie bazy + role) z kontenera kanban_postgres.
# Uzycie: bash docker/postgres/backup.sh    (kanban_postgres musi dzialac)
# Kanban to tylko jedna z baz na tym serwerze — pg_dumpall bierze wszystko.
set -euo pipefail

dir="$(cd "$(dirname "$0")" && pwd)/backups"
mkdir -p "$dir"
stamp="$(date +%Y-%m-%d_%H-%M)"
out="$dir/postgres_${stamp}.sql.gz"

# kanban to superuser (POSTGRES_USER przy initdb), wiec pg_dumpall ma dostep do wszystkiego.
docker exec kanban_postgres pg_dumpall -U kanban | gzip > "$out"
echo "Backup: $out ($(du -h "$out" | cut -f1))"

# Retencja: zostaw 14 najnowszych zrzutow.
ls -1t "$dir"/postgres_*.sql.gz 2>/dev/null | tail -n +15 | xargs -r rm --
