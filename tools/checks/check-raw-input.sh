#!/bin/bash
# Fails when first-party PHP indexes request superglobals directly ($_GET['x'],
# $_POST[...], $_REQUEST[...], $_COOKIE[...]) more often than recorded in
# tools/checks/raw-input-baseline.txt. New code must read input through
# dPgetParam()/dPgetCleanParam()/dPgetIntParam(), which validate and type it.
#
#   tools/checks/check-raw-input.sh            check (CI)
#   tools/checks/check-raw-input.sh --update   rewrite the baseline after removing raw uses
set -euo pipefail
cd "$(dirname "$0")/../.."
BASELINE=tools/checks/raw-input-baseline.txt
PATTERN='\$_(GET|POST|REQUEST|COOKIE)[[:space:]]*\['

current() {
  git ls-files '*.php' '*.inc' \
    | grep -vE '^(lib|vendor|tests|docker)/|^modules/(holiday/PEAR)/' \
    | xargs grep -cE "$PATTERN" 2>/dev/null \
    | grep -v ':0$' | sort || true
}

if [ "${1:-}" = "--update" ]; then
  current > "$BASELINE"
  echo "Baseline updated: $(wc -l < "$BASELINE") files, $(awk -F: '{s+=$2} END{print s+0}' "$BASELINE") raw uses."
  exit 0
fi

fail=0
while IFS=: read -r file count; do
  allowed=$(awk -F: -v f="$file" '$1 == f { print $2 }' "$BASELINE")
  allowed=${allowed:-0}
  if [ "$count" -gt "$allowed" ]; then
    echo "$file: $count raw superglobal reads (baseline $allowed)"
    grep -nE "$PATTERN" "$file" | sed 's/^/    /'
    fail=1
  fi
done < <(current)

if [ $fail -ne 0 ]; then
  echo
  echo "Read request input with dPgetParam()/dPgetCleanParam()/dPgetIntParam() instead."
  echo "If you removed raw uses elsewhere, run tools/checks/check-raw-input.sh --update."
  exit 1
fi
echo "OK: no new raw superglobal reads."
