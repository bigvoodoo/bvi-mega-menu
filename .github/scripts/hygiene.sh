#!/usr/bin/env bash
#
# Local mirror of the file-hygiene job in .github/workflows/plugin-checks.yml
# Usage: npm run hygiene
#
# Fails on: trailing whitespace, CRLF line endings, closing ?> tags in src/,
# missing final newlines, lines > 120 chars, bad indentation (2-space for
# json/yml/yaml/js/jsx/ts, 4-space for php/scss/css/html/hbs; no tabs).

set -uo pipefail

if [ -t 1 ]; then
    C_RESET=$'\e[0m'; C_RED=$'\e[31m'; C_GREEN=$'\e[32m'
    C_YEL=$'\e[33m';  C_BOLD=$'\e[1m'
else
    C_RESET=""; C_RED=""; C_GREEN=""; C_YEL=""; C_BOLD=""
fi

EXIT_CODE=0
section() { printf '\n%s▸ %s%s\n' "$C_BOLD" "$1" "$C_RESET"; }
pass()    { printf '  %s✓%s %s\n' "$C_GREEN" "$C_RESET" "$1"; }
fail()    { printf '  %s✗%s %s\n' "$C_RED"   "$C_RESET" "$1"; EXIT_CODE=1; }
warn()    { printf '  %s!%s %s\n' "$C_YEL"   "$C_RESET" "$1"; }

EXCLUDE_DIRS=(--exclude-dir=vendor --exclude-dir=node_modules
              --exclude-dir=dist --exclude-dir=.ORIGINAL --exclude-dir=.claude)
CODE_GLOBS=(--include='*.php' --include='*.js' --include='*.jsx'
            --include='*.scss' --include='*.css' --include='*.html' --include='*.hbs'
            --include='*.json' --include='*.yml' --include='*.yaml' --include='*.ts')

# ── trailing whitespace ──────────────────────────────────────────────────
section "Trailing whitespace"
if grep -rn ' $' "${CODE_GLOBS[@]}" . "${EXCLUDE_DIRS[@]}" 2>/dev/null | head -20; then
    fail "Trailing whitespace found (see above)"
else
    pass "No trailing whitespace"
fi

# ── LF-only line endings ─────────────────────────────────────────────────
section "Line endings"
CRLF_HITS=$(grep -rIl $'\r' "${CODE_GLOBS[@]}" . "${EXCLUDE_DIRS[@]}" 2>/dev/null || true)
if [ -n "$CRLF_HITS" ]; then
    echo "$CRLF_HITS"
    fail "CRLF line endings found (use LF only)"
else
    pass "LF line endings"
fi

# ── no closing ?> in src/ ────────────────────────────────────────────────
section "Closing PHP tags in src/"
BAD_CLOSE=0
while IFS= read -r -d '' f; do
    LAST=$(tail -c 10 "$f" | tr -d '[:space:]')
    if [[ "$LAST" == *"?>" ]]; then
        echo "  $f"
        BAD_CLOSE=1
    fi
done < <(find src/ -name '*.php' -print0 2>/dev/null)
[ $BAD_CLOSE -eq 1 ] && fail "Closing ?> tag found" || pass "No closing PHP tags"

# ── final newline ────────────────────────────────────────────────────────
section "Final newline"
MISSING_NL=0
while IFS= read -r -d '' f; do
    if [ -s "$f" ] && [ -n "$(tail -c 1 "$f" 2>/dev/null || true)" ]; then
        echo "  $f"
        MISSING_NL=1
    fi
done < <(find . \( -name '*.php' -o -name '*.js' -o -name '*.jsx' -o -name '*.scss' \
    -o -name '*.css' -o -name '*.html' -o -name '*.hbs' \
    -o -name '*.json' -o -name '*.yml' -o -name '*.yaml' -o -name '*.ts' \) \
    -not -path './vendor/*' -not -path './node_modules/*' \
    -not -path './assets/dist/*' -not -path '*/.ORIGINAL/*' -print0 2>/dev/null)
[ $MISSING_NL -eq 1 ] && fail "Files missing final newline" || pass "All files end with newline"

# ── max line length ──────────────────────────────────────────────────────
section "Line length ≤ 120"
LONG_TOTAL=0
while IFS= read -r -d '' f; do
    if LONG=$(python3 -c "
import sys
with open(sys.argv[1], encoding='utf-8', errors='replace') as fh:
    for i, line in enumerate(fh, 1):
        n = len(line.rstrip('\n'))
        if n > 120:
            print(f'{sys.argv[1]}:{i}: {n} chars')
" "$f") && [ -n "$LONG" ]; then
        echo "$LONG"
        LONG_TOTAL=$((LONG_TOTAL + 1))
    fi
done < <(find src/ assets/src/ \( -name '*.php' -o -name '*.js' -o -name '*.jsx' -o -name '*.scss' \
    -o -name '*.css' -o -name '*.html' -o -name '*.hbs' \
    -o -name '*.json' -o -name '*.yml' -o -name '*.yaml' -o -name '*.ts' \) \
    -not -path '*/.ORIGINAL/*' -print0 2>/dev/null)
[ $LONG_TOTAL -gt 0 ] && fail "$LONG_TOTAL file(s) with lines > 120 chars" || pass "All lines ≤ 120 chars"

# ── indentation: 2-space for json/yml/js/ts, 4-space for the rest ───────
section "Indentation"
TWO_HITS=$(grep -rPn '^(\t|(  )* (?!\*)\S)' \
    --include='*.json' --include='*.yml' --include='*.yaml' \
    --include='*.js' --include='*.jsx' --include='*.ts' \
    --exclude='package-lock.json' \
    . "${EXCLUDE_DIRS[@]}" 2>/dev/null || true)
FOUR_HITS=$(grep -rPn '^(\t|(    )* {1,3}(?!\*)\S)' \
    --include='*.php' --include='*.scss' --include='*.css' \
    --include='*.html' --include='*.hbs' \
    --exclude-dir=templates \
    . "${EXCLUDE_DIRS[@]}" 2>/dev/null || true)
INDENT_BAD=0
if [ -n "$TWO_HITS" ];  then echo "$TWO_HITS";  INDENT_BAD=1; fi
if [ -n "$FOUR_HITS" ]; then echo "$FOUR_HITS"; INDENT_BAD=1; fi
if [ $INDENT_BAD -eq 1 ]; then
    fail "Bad indentation (2 spaces for JSON/YAML/JS/TS, 4 for everything else; no tabs)"
else
    pass "Indentation correct"
fi

# ── summary ──────────────────────────────────────────────────────────────
echo
if [ $EXIT_CODE -eq 0 ]; then
    printf '%s%s✓ Hygiene OK%s\n' "$C_GREEN" "$C_BOLD" "$C_RESET"
else
    printf '%s%s✗ Hygiene failed%s\n' "$C_RED" "$C_BOLD" "$C_RESET"
fi
exit $EXIT_CODE
