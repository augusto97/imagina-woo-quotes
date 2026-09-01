#!/usr/bin/env bash
#
# Genera un zip instalable del plugin.
#
# Instala las dependencias de producción, copia solo lo que debe distribuirse
# y comprime el resultado.

set -euo pipefail

SLUG="imagina-woo-quotes"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC="$ROOT/$SLUG"
OUT="$ROOT/build"

VERSION="$(grep -m1 "^ \* Version:" "$SRC/$SLUG.php" | awk '{print $3}')"

echo "Empaquetando $SLUG $VERSION"

if command -v composer >/dev/null 2>&1; then
	composer install --no-dev --optimize-autoloader --working-dir="$SRC" --quiet
else
	echo "AVISO: composer no está instalado; el zip saldrá sin la librería de PDF." >&2
fi

rm -rf "$OUT"
mkdir -p "$OUT/$SLUG"

# Se excluyen los archivos de desarrollo y las pruebas de las dependencias.
rsync -a \
	--exclude='.git*' \
	--exclude='node_modules' \
	--exclude='*.map' \
	--exclude='tests/' \
	--exclude='composer.json' \
	--exclude='composer.lock' \
	"$SRC/" "$OUT/$SLUG/"

( cd "$OUT" && zip -qr "../$SLUG-$VERSION.zip" "$SLUG" )

echo "Listo: $ROOT/$SLUG-$VERSION.zip"
