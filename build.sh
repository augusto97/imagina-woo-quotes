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
CONSTANT="$(grep -oP "define\( 'IWQ_VERSION', '\K[^']+" "$SRC/$SLUG.php")"

# La cabecera y la constante deben coincidir: la primera la lee WordPress,
# la segunda versiona los assets. Si divergen, la caché del navegador sirve
# CSS y JS viejos con PHP nuevo.
if [[ "$VERSION" != "$CONSTANT" ]]; then
	echo "ERROR: la cabecera dice $VERSION pero IWQ_VERSION es $CONSTANT. Usa ./bump-version.sh" >&2
	exit 1
fi

if ! grep -q "## \[$VERSION\]" "$ROOT/CHANGELOG.md"; then
	echo "ERROR: CHANGELOG.md no tiene una sección para $VERSION" >&2
	exit 1
fi

echo "Empaquetando $SLUG $VERSION"

if command -v composer >/dev/null 2>&1; then
	# composer.lock está versionado: el build instala exactamente lo mismo en
	# cualquier máquina.
	composer install --no-dev --optimize-autoloader --working-dir="$SRC" --quiet
else
	echo "AVISO: composer no está instalado; el zip saldrá sin la librería de PDF." >&2
fi

rm -rf "$OUT"
mkdir -p "$OUT/$SLUG"

# Se excluyen los archivos de desarrollo y las pruebas de las dependencias.
# Se usa tar en lugar de rsync porque está en cualquier máquina.
( cd "$SRC/.." && tar -cf - \
	--exclude='.git*' \
	--exclude='node_modules' \
	--exclude='*.map' \
	--exclude='*/tests' \
	--exclude='*/vendor/*/*/*.md' \
	--exclude='*/vendor/*/*/.php-cs-fixer*' \
	--exclude='*/vendor/*/*/phpunit*' \
	--exclude='composer.json' \
	--exclude='composer.lock' \
	"$SLUG" ) | ( cd "$OUT" && tar -xf - )

# Ningún archivo PHP del paquete puede tener errores de sintaxis: un zip
# roto tumba el admin de quien lo instale.
LINT_ERRORS=0
while IFS= read -r -d '' PHP_FILE; do
	if ! php -l "$PHP_FILE" >/dev/null 2>&1; then
		echo "Error de sintaxis en $PHP_FILE" >&2
		LINT_ERRORS=$(( LINT_ERRORS + 1 ))
	fi
done < <( find "$OUT/$SLUG" -name "*.php" -not -path "*/vendor/*" -print0 )
[[ "$LINT_ERRORS" -eq 0 ]] || { echo "Abortado: $LINT_ERRORS archivo(s) con errores de sintaxis." >&2; exit 1; }

( cd "$OUT" && zip -qr "../$SLUG-$VERSION.zip" "$SLUG" -x "*.DS_Store" )

( cd "$ROOT" && sha256sum "$SLUG-$VERSION.zip" > "$SLUG-$VERSION.zip.sha256" )

echo "Listo: $ROOT/$SLUG-$VERSION.zip ($(du -h "$ROOT/$SLUG-$VERSION.zip" | cut -f1))"
echo "SHA-256: $(cut -d' ' -f1 "$ROOT/$SLUG-$VERSION.zip.sha256")"
