#!/usr/bin/env bash
# e2e w powtarzalnym srodowisku (Docker): przegladarki + PHP w obrazie, testy z repo.
# Wymaga: kanban_postgres dziala w sieci kanban_net, a baza kanban_urzedowy_test jest zmigrowana.
set -euo pipefail

cd "$(dirname "$0")/.."

# Obraz pobierany z repo mafio69 (docker sam sciagnie, jesli go nie ma lokalnie).
# Maintainer publikuje go przez scripts/e2e-image-publish.sh.
IMAGE="${E2E_IMAGE:-mafio69/kanban-e2e:1.48.0}"

# Testy w kontenerze, wpiete w baze testowa po sieci kanban_net.
# node_modules jako anonimowy wolumin — nie mieszamy z host-owym.
docker run --rm \
    --network kanban_net \
    -e DB_HOST=kanban_postgres \
    -e DB_NAME=kanban_urzedowy_test \
    -v "$PWD":/app \
    -v /app/node_modules \
    -w /app \
    "$IMAGE" \
    sh -c "npm ci && npx playwright install chromium && npm run e2e"
