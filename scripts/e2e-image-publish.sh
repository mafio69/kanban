#!/usr/bin/env bash
# Publikacja obrazu e2e do repo mafio69 (Docker Hub). Robi to maintainer (Mariusz) raz.
# Wymaga wczesniejszego: docker login
# Uzycie: bash scripts/e2e-image-publish.sh [tag]     (domyslnie 1.48.0)
set -euo pipefail

cd "$(dirname "$0")/.."
tag="${1:-1.48.0}"
image="mafio69/kanban-e2e"

docker build -t "${image}:${tag}" -t "${image}:latest" docker/e2e
docker push "${image}:${tag}"
docker push "${image}:latest"

echo "Opublikowano: ${image}:${tag} (+ latest)"
