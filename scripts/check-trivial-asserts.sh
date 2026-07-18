#!/usr/bin/env bash
# Wykrywa trywialne asercje w testach (zawsze przechodza, nic nie sprawdzaja).
# Uzycie: bash scripts/check-trivial-asserts.sh [katalog]   (domyslnie tests)
# Zwraca 1, gdy znajdzie trywialne asercje - nadaje sie do CI / pre-commit. Wymaga grep -P (GNU grep).
set -uo pipefail

dir="${1:-tests}"

if [ ! -d "$dir" ]; then
  echo "Brak katalogu: $dir" >&2
  exit 2
fi

EXCLUDE=(--exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=venv --exclude-dir=.venv)

# Kazdy wzorzec osobno (backreference \1 dziala tylko w obrebie jednego wzorca).
patterns=(
  'assertTrue\s*\(\s*true\s*\)'
  'assertFalse\s*\(\s*false\s*\)'
  'assertNull\s*\(\s*null\s*\)'
  'assertNotNull\s*\(\s*true\s*\)'
  'assertEmpty\s*\(\s*(\[\s*\]|array\s*\(\s*\)|""|'"''"'|0)\s*\)'
  # ten sam literal po obu stronach: assertSame(true, true), assertEquals(1, 1)
  'assert(?:Same|Equals)\s*\(\s*(true|false|null|[0-9]+)\s*,\s*\1\s*\)'
  # ta sama zmienna po obu stronach: assertEquals($x, $x)
  'assert(?:Same|Equals)\s*\(\s*(\$[A-Za-z_][A-Za-z0-9_]*)\s*,\s*\1\s*\)'
  # Playwright / JS
  'expect\s*\(\s*true\s*\)\s*\.\s*(?:toBe|toBeTruthy|toEqual)\s*\(\s*(?:true)?\s*\)'
  'expect\s*\(\s*1\s*\)\s*\.\s*toBe\s*\(\s*1\s*\)'
)

hits=""
for p in "${patterns[@]}"; do
  found=$(grep -rPnI "${EXCLUDE[@]}" --include='*.php' --include='*.js' --include='*.ts' "$p" "$dir" || true)
  [ -n "$found" ] && hits+="$found"$'\n'
done

# markTestSkipped/Incomplete: tylko OSTRZEZENIE (swiadomy skip z powodem jest OK), nie blokada.
skipped=$(grep -rEnI "${EXCLUDE[@]}" --include='*.php' 'markTest(Skipped|Incomplete)\s*\(' "$dir" || true)

hits=$(printf '%s' "$hits" | sed '/^$/d' | sort -u)

if [ -n "$skipped" ]; then
  echo "UWAGA (nie blokuje) - testy pominiete/niekompletne:" >&2
  echo "$skipped" | sed 's/^/  /' >&2
fi

if [ -z "$hits" ]; then
  echo "OK: brak trywialnych asercji w $dir"
  exit 0
fi

echo "BLOKADA - trywialne asercje (zawsze zielone, nic nie sprawdzaja):" >&2
echo "$hits" | sed 's/^/  /' >&2
exit 1
