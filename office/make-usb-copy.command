#!/bin/bash
# Makes the EMOTracker folder to copy to the office laptop (with a USB drive):
# the app's screens built, the backend with only what it needs to run, and
# the office files. Leaves out this Mac's settings (.env), logs, uploaded files,
# the frontend source and the paper. Double-click it, or run it in Terminal.
#
#   office/make-usb-copy.command              the program only (for updates)
#   office/make-usb-copy.command --with-data  plus a backup of this Mac's data,
#                                             loaded by Setup on the office laptop
set -euo pipefail
cd "$(dirname "$0")/.."
ROOT="$PWD"
DEST="$HOME/Desktop/EMO Tracker USB"
OUT="$DEST/EMOTracker"

echo "1. Building the app's screens..."
(cd frontend && npm run build --silent)

echo "2. Copying the files to: $OUT"
rm -rf "$OUT"
mkdir -p "$OUT"
# Files in the repository (and new ones not yet committed), minus this script.
git ls-files --cached --others --exclude-standard -- backend office \
  | grep -v '^office/make-usb-copy.command$' \
  | rsync -a --files-from=- "$ROOT/" "$OUT/"
rsync -a "$ROOT/backend/public/app/" "$OUT/backend/public/app/"

echo "3. Adding the backend's libraries (without the development tools)..."
(cd "$OUT/backend" && composer install --no-dev --optimize-autoloader --no-interaction --no-progress --quiet)

if [[ "${1:-}" == "--with-data" ]]; then
  echo "4. Adding a backup of this Mac's data..."
  NAME="office-$(date +%Y-%m-%d-%H%M)"
  SAVED=$(cd backend && php artisan backup:run --name="$NAME" | sed -n 's/^Snapshot saved: //p')
  mkdir -p "$OUT/office/data"
  cp "$SAVED" "$OUT/office/data/"
  echo "   $(basename "$SAVED")"
fi

# USB drives (FAT32, exFAT) can't hold links.
if [[ -n "$(find "$OUT" -type l | head -1)" ]]; then
  echo "Warning: the copy has links that a USB drive can't hold:"
  find "$OUT" -type l
fi

echo
echo "Done: $(du -sh "$OUT" | cut -f1). Copy this folder to the USB drive:"
echo "  $OUT"
echo "Then follow office/INSTALL.md."
