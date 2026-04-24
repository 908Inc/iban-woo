#!/usr/bin/env bash
#
# Bump plugin version across all places it is referenced.
#
# Usage:
#   ./scripts/bump-version.sh <new-version>
#
# Example:
#   ./scripts/bump-version.sh 0.1.1
#
set -euo pipefail

if [ $# -ne 1 ]; then
  echo "Usage: $0 <new-version>"
  echo "Example: $0 0.1.1"
  exit 1
fi

NEW_VERSION="$1"

if ! echo "$NEW_VERSION" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+$'; then
  echo "ERROR: version must be semver-like X.Y.Z (got: $NEW_VERSION)"
  exit 1
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC_DIR="${ROOT_DIR}/src"

MAIN_PHP="${SRC_DIR}/opendatabot-iban.php"
README_TXT="${SRC_DIR}/readme.txt"
POT_FILE="${SRC_DIR}/languages/opendatabot-iban.pot"
PO_FILES=("${SRC_DIR}/languages/opendatabot-iban-uk.po" "${SRC_DIR}/languages/opendatabot-iban-ru_RU.po")

if [ ! -f "$MAIN_PHP" ]; then
  echo "ERROR: $MAIN_PHP not found"
  exit 1
fi

CURRENT_VERSION=$(grep -E '^[[:space:]]*\*[[:space:]]*Version:' "$MAIN_PHP" | head -1 | sed -E 's/.*Version:[[:space:]]+([0-9A-Za-z.\-]+).*/\1/')

echo "Bumping: ${CURRENT_VERSION} → ${NEW_VERSION}"

# Portable sed -i (macOS/BSD vs GNU)
sed_inplace() {
  if sed --version >/dev/null 2>&1; then
    sed -i "$@"
  else
    sed -i '' "$@"
  fi
}

# 1. Plugin header
sed_inplace -E "s/^([[:space:]]*\*[[:space:]]*Version:[[:space:]]+).*/\1${NEW_VERSION}/" "$MAIN_PHP"

# 2. Constant OPENDATABOT_IBAN_VERSION
sed_inplace -E "s/(define\([[:space:]]*'OPENDATABOT_IBAN_VERSION',[[:space:]]*')[^']+('[[:space:]]*\))/\1${NEW_VERSION}\2/" "$MAIN_PHP"

# 3. readme.txt Stable tag
if [ -f "$README_TXT" ]; then
  sed_inplace -E "s/^(Stable tag:[[:space:]]*).*/\1${NEW_VERSION}/" "$README_TXT"
fi

# 4. Project-Id-Version in .pot / .po (match only digits + dots to avoid eating the trailing \n")
for f in "$POT_FILE" "${PO_FILES[@]}"; do
  [ -f "$f" ] || continue
  sed_inplace -E "s/(Project-Id-Version: Opendatabot IBAN Invoice )[0-9.]+/\1${NEW_VERSION}/" "$f"
done

echo ""
echo "Updated files:"
echo "  - $MAIN_PHP            (Version header, OPENDATABOT_IBAN_VERSION)"
[ -f "$README_TXT" ] && echo "  - $README_TXT           (Stable tag)"
[ -f "$POT_FILE" ]   && echo "  - $POT_FILE              (Project-Id-Version)"
for f in "${PO_FILES[@]}"; do
  [ -f "$f" ] && echo "  - $f               (Project-Id-Version)"
done
echo ""
echo "Verify:"
grep -E '^[[:space:]]*\*[[:space:]]*Version:|OPENDATABOT_IBAN_VERSION' "$MAIN_PHP" | sed 's/^/  /'
[ -f "$README_TXT" ] && grep -E '^Stable tag:' "$README_TXT" | sed 's/^/  /'
echo ""
echo "Next steps:"
echo "  - Update changelog in $README_TXT"
echo "  - Commit changes: git add -A && git commit -m 'bump: ${NEW_VERSION}'"
echo "  - Build + tag:    ./scripts/release.sh"
