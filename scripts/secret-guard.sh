#!/usr/bin/env bash
# Blokada sekretow. Tryby:
#   --staged  : skanuje TYLKO dodane linie w staged plikach (git pre-commit)
#   (bez arg) : skanuje wszystkie SLEDZONE pliki (CI)
# Zwraca 1, gdy cos znajdzie. Nie wymaga zewnetrznych narzedzi (git + grep).
set -uo pipefail

root="$(git rev-parse --show-toplevel)"
mode="${1:-all}"

# Wzorce sekretow (specyficzne, zeby nie lapac placeholderow typu "your_api_key_here").
patterns=(
  'sk-[A-Za-z0-9]{20}'                          # OpenAI/Anthropic
  'sk-proj-[A-Za-z0-9_-]{20}'                    # OpenAI project key
  'pplx-[A-Za-z0-9]{20}'                         # Perplexity
  'AKIA[0-9A-Z]{16}'                             # AWS access key
  'AIza[0-9A-Za-z_-]{35}'                        # Google API key
  'ghp_[A-Za-z0-9]{36}'                          # GitHub token
  'github_pat_[A-Za-z0-9_]{22,}'                 # GitHub fine-grained PAT
  'xox[baprs]-[A-Za-z0-9-]{10,}'                 # Slack
  'APP_SECRET=[A-Fa-f0-9]{16,}'                  # Symfony APP_SECRET (realny hex)
  'BEGIN [A-Z ]*PRIVATE KEY'                     # klucze prywatne (RSA/EC/OpenSSH/PGP)
)
joined=$(IFS='|'; echo "${patterns[*]}")

# Pomijamy: atrapy env (example/test), zaleznosci, binaria/teksty niekodowe.
skip='(\.env\.(example|test)$|(^|/)vendor/|(^|/)node_modules/|\.(md|txt|lock|svg|png|jpe?g|gif|ico)$)'

hits=""
if [ "$mode" = "--staged" ]; then
  files=$(git diff --cached --name-only --diff-filter=ACM | grep -vE "$skip" || true)
  for f in $files; do
    [ -f "$f" ] || continue
    m=$(git diff --cached -- "$f" | grep '^+' | grep -v '^+++' | grep -Ec "$joined" || true)
    [ "${m:-0}" -gt 0 ] && hits+="$f"$'\n'
  done
else
  hits=$(git -C "$root" grep -lIE "$joined" -- . 2>/dev/null | grep -vE "$skip" || true)
fi

hits=$(printf '%s' "$hits" | sed '/^$/d' | sort -u)

if [ -z "$hits" ]; then
  echo "secret-guard: brak sekretow"
  exit 0
fi

echo "BLOKADA - mozliwe sekrety w plikach:" >&2
echo "$hits" | sed 's/^/  /' >&2
echo "Sekrety trzymaj w .env (poza repo). Atrapy oznaczaj jako test-fake-key." >&2
exit 1
