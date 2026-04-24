#!/usr/bin/env bash
#
# Simple release:
#   - reads version from the plugin header
#   - checks git tree is clean and tag does not yet exist
#   - builds opendatabot-iban-<version>.zip
#   - creates annotated git tag v<version>
#
# Usage:
#   ./scripts/release.sh
#
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC_DIR="${ROOT_DIR}/src"
MAIN_PHP="${SRC_DIR}/opendatabot-iban.php"

cd "$ROOT_DIR"

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "ERROR: not a git repository. Run: git init && git add -A && git commit -m 'init'"
  exit 1
fi

VERSION=$(grep -E '^[[:space:]]*\*[[:space:]]*Version:' "$MAIN_PHP" | head -1 | sed -E 's/.*Version:[[:space:]]+([0-9A-Za-z.\-]+).*/\1/')

if [ -z "$VERSION" ]; then
  echo "ERROR: could not extract version from $MAIN_PHP"
  exit 1
fi

TAG="v${VERSION}"

if [ -n "$(git status --porcelain)" ]; then
  echo "ERROR: working tree is not clean. Commit or stash your changes first:"
  git status --short
  exit 1
fi

if git rev-parse --verify --quiet "refs/tags/${TAG}" >/dev/null; then
  echo "ERROR: tag ${TAG} already exists. Bump version via: ./scripts/bump-version.sh <new-version>"
  exit 1
fi

echo "==> Building ${TAG} ..."
"${ROOT_DIR}/scripts/build-plugin-zip.sh"

ZIP_PATH="${ROOT_DIR}/opendatabot-iban-${VERSION}.zip"

echo ""
echo "==> Tagging ${TAG} ..."
git tag -a "${TAG}" -m "Release ${VERSION}"

echo ""
echo "Done."
echo "  Archive: ${ZIP_PATH}"
echo "  Tag:     ${TAG}"
echo ""
echo "To push the tag to the remote:"
echo "  git push origin ${TAG}"
