#!/bin/bash
# Fails when first-party code gains inline JavaScript, which the
# Content-Security-Policy blocks: event-handler attributes (onclick=, ...),
# javascript: URLs, and <script> blocks without src, nonce or a JSON type.
# Counts per file are recorded in tools/checks/inline-js-baseline.txt; a converted
# file must stay at its new count. See docs/csp.md for how to convert.
#
#   tools/checks/check-inline-js.sh              check (CI)
#   tools/checks/check-inline-js.sh --update     rewrite the baseline after converting
#   tools/checks/check-inline-js.sh --list PATH  show the remaining lines under PATH
set -euo pipefail
cd "$(dirname "$0")/../.."
BASELINE=tools/checks/inline-js-baseline.txt
EVENTS='click|dblclick|change|submit|reset|load|unload|beforeunload|error|resize|scroll|focus|blur|input|select|key(down|up|press)|mouse(down|up|over|out|move|enter|leave)|contextmenu|drag(start|end|over|enter|leave)?|drop'
# Comment lines (//, #, /*, *) are skipped.
# Case-insensitive (onClick=, JavaScript:), and a handler may start an attribute
# string ('onChange="..."' passed to arraySelect()), so "on" may follow a space,
# a quote or the start of the line.
PATTERN="(?i)^(?!\\s*(//|#|/\\*|\\*)).*((^|[\\s'\"])on($EVENTS)\\s*=|javascript:|<script(?![^>]*\\b(src|nonce)\\s*=)(?![^>]*application/json))"

files() {
  git ls-files '*.php' '*.inc' '*.html' '*.htm' '*.js' \
    | grep -vE '^(lib|vendor|tests|tools|docker|docs|node_modules)/|/(examples?|docs?|demo|api|jsLibraries|jscalendar|PEAR)/|\.min\.js$|/jquery[^/]*\.js$'
}

current() {
  files | tr '\n' '\0' | xargs -0 grep -cP "$PATTERN" 2>/dev/null | grep -v ':0$' | sort || true
}

case "${1:-}" in
  --update)
    current > "$BASELINE"
    echo "Baseline updated: $(wc -l < "$BASELINE") files, $(awk -F: '{s+=$NF} END{print s+0}' "$BASELINE") lines with inline JavaScript."
    exit 0 ;;
  --list)
    files | grep -E "^${2:-.}" | tr '\n' '\0' | xargs -0 grep -nP "$PATTERN" 2>/dev/null || true
    exit 0 ;;
esac

fail=0
while IFS= read -r line; do
  file=${line%:*}; count=${line##*:}
  allowed=$(awk -v f="$file" '{ n = split($0, a, ":"); c = a[n]; sub(/:[0-9]+$/, ""); if ($0 == f) print c }' "$BASELINE")
  allowed=${allowed:-0}
  if [ "$count" -gt "$allowed" ]; then
    echo "$file: $count lines with inline JavaScript (baseline $allowed)"
    grep -nP "$PATTERN" "$file" | sed 's/^/    /' | head -20
    fail=1
  fi
done < <(current)

if [ $fail -ne 0 ]; then
  echo
  echo "Use dPaction() / data-dp-* attributes, a .js file, or a nonce (docs/csp.md)."
  echo "After converting code, run tools/checks/check-inline-js.sh --update."
  exit 1
fi
echo "OK: no new inline JavaScript."
