#!/usr/bin/env bash
# Instaluje hooki ochrony testow do .git/hooks. Uruchom z katalogu repo: bash scripts/install-git-hooks.sh
set -euo pipefail

root=$(git rev-parse --show-toplevel)
src="$root/scripts/git-hooks"
dst="$root/.git/hooks"

for hook in pre-commit commit-msg; do
  cp "$src/$hook" "$dst/$hook"
  chmod +x "$dst/$hook"
  echo "zainstalowano: $dst/$hook"
done

echo "Gotowe. Zmiana tests/** wymaga teraz: plik .allow-test-change ORAZ [allow-test-change] w opisie commita."
