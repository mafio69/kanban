#!/usr/bin/env bash
# Aktywuje hooki z .githooks/ (secret-guard + trivial-guard + ochrona testow).
# Jedna komenda, hooki wersjonowane w repo — bez kopiowania do .git/hooks.
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"
git config core.hooksPath .githooks
chmod +x .githooks/* scripts/*.sh 2>/dev/null || true

echo "Aktywowano .githooks/ (core.hooksPath)."
echo "Bramki: sekrety, trywialne asercje, ochrona tests/**."
