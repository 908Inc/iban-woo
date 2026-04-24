#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC_DIR="${ROOT_DIR}/src"
PLUGIN_SLUG="opendatabot-iban"
MAIN_PHP="${SRC_DIR}/${PLUGIN_SLUG}.php"

if [ ! -f "$MAIN_PHP" ]; then
  echo "ERROR: Missing $MAIN_PHP"
  exit 1
fi

VERSION=$(grep -E '^[[:space:]]*\*[[:space:]]*Version:' "$MAIN_PHP" | head -1 | sed -E 's/.*Version:[[:space:]]+([0-9A-Za-z.\-]+).*/\1/')

if [ -z "$VERSION" ]; then
  echo "ERROR: could not extract version from $MAIN_PHP"
  exit 1
fi

ZIP_PATH="${ROOT_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"

# Compile .po -> .mo if msgfmt is available.
if command -v msgfmt >/dev/null 2>&1; then
  for po in "${SRC_DIR}"/languages/*.po; do
    [ -e "$po" ] || continue
    mo="${po%.po}.mo"
    echo "Compiling ${po##*/} -> ${mo##*/}"
    msgfmt -o "$mo" "$po"
  done
else
  echo "NOTE: msgfmt not found — skipping .po -> .mo compilation."
fi

rm -f "${ZIP_PATH}"

# Stage files under <slug>/ so the zip unpacks into wp-content/plugins/<slug>/.
STAGE_DIR="$(mktemp -d)"
trap 'rm -rf "${STAGE_DIR}"' EXIT
mkdir -p "${STAGE_DIR}/${PLUGIN_SLUG}"
# Use tar to copy contents (preserves dot-files, excludes .DS_Store).
(cd "${SRC_DIR}" && tar --exclude='.DS_Store' -cf - .) | (cd "${STAGE_DIR}/${PLUGIN_SLUG}" && tar -xf -)

echo "Building ${ZIP_PATH##*/} (version ${VERSION}) ..."
(cd "${STAGE_DIR}" && zip -rq "${ZIP_PATH}" "${PLUGIN_SLUG}")

echo "Done: ${ZIP_PATH}"
