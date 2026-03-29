#!/bin/sh
set -eu

# Hostinger shared hosting serves this repository directly from public_html.
# Laravel still builds browser assets into public/, so copy those files to the
# web root after each pull instead of using rsync.

APP_ROOT="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
PUBLIC_DIR="$APP_ROOT/public"

if [ ! -d "$PUBLIC_DIR" ]; then
    echo "public directory not found: $PUBLIC_DIR" >&2
    exit 1
fi

rm -rf "$APP_ROOT/build"
cp -R "$PUBLIC_DIR/build" "$APP_ROOT/build"

for file in manifest.webmanifest sw.js robots.txt favicon.ico .htaccess; do
    if [ -f "$PUBLIC_DIR/$file" ]; then
        cp "$PUBLIC_DIR/$file" "$APP_ROOT/$file"
    fi
done

echo "Hostinger public assets synced into $APP_ROOT"
