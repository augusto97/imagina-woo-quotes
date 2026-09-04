#!/usr/bin/env bash
#
# Publica el zip de la versión actual en la rama `release`.
#
# La rama contiene solo tres archivos: imagina-woo-quotes.zip (siempre la
# última versión, con nombre fijo), su suma SHA-256 y un README con la
# versión. Cada publicación reemplaza el zip anterior; el historial de
# cambios vive en CHANGELOG.md de la rama de código.
#
# Uso: ./publish-release.sh            (tras ./build.sh)

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN="$ROOT/imagina-woo-quotes/imagina-woo-quotes.php"
VERSION="$(grep -m1 "^ \* Version:" "$MAIN" | awk '{print $3}')"
ZIP="$ROOT/imagina-woo-quotes-$VERSION.zip"
BRANCH="$(git -C "$ROOT" rev-parse --abbrev-ref HEAD)"

[[ -f "$ZIP" ]] || { echo "Falta $ZIP: ejecuta ./build.sh primero." >&2; exit 1; }
[[ -z "$(git -C "$ROOT" status --porcelain)" ]] || { echo "Hay cambios sin confirmar: haz commit antes de publicar." >&2; exit 1; }

TMP="$(mktemp -d)"
cp "$ZIP" "$TMP/imagina-woo-quotes.zip"
( cd "$TMP" && sha256sum imagina-woo-quotes.zip > imagina-woo-quotes.zip.sha256 )
SHA="$(cut -d' ' -f1 "$TMP/imagina-woo-quotes.zip.sha256")"
SIZE="$(du -h "$ZIP" | cut -f1)"

cat > "$TMP/README.md" <<README
# Imagina Woo Quotes

**Versión actual: $VERSION** · $(date +%Y-%m-%d) · $SIZE

Esta rama contiene únicamente el zip instalable de la última versión, con
nombre fijo, para que siempre sepas cuál descargar:

- [\`imagina-woo-quotes.zip\`](imagina-woo-quotes.zip)
- SHA-256: \`$SHA\`

## Instalación

En WordPress: **Plugins → Añadir nuevo → Subir plugin**, elige el zip y activa.
Para actualizar, sube el zip nuevo con «Reemplazar el actual». Los ajustes y
los presupuestos se conservan.

Con WP-CLI:

\`\`\`bash
wp plugin install imagina-woo-quotes.zip --activate   # o --force para actualizar
\`\`\`

Verificar la descarga:

\`\`\`bash
sha256sum -c imagina-woo-quotes.zip.sha256
\`\`\`

Requisitos: WordPress 6.4+, WooCommerce 8.0+, PHP 8.1+. El zip incluye dompdf:
no hace falta Composer en el servidor.

El historial de cambios está en \`CHANGELOG.md\` de la rama de código.
README

# El commit se construye con plumbing, sin tocar el árbol de trabajo: tres
# blobs, un árbol y un commit encadenado al último release (si existe).
git -C "$ROOT" fetch -q origin release 2>/dev/null || true
PARENT="$(git -C "$ROOT" rev-parse -q --verify refs/heads/release 2>/dev/null || true)"

B_ZIP="$(git -C "$ROOT" hash-object -w "$TMP/imagina-woo-quotes.zip")"
B_SHA="$(git -C "$ROOT" hash-object -w "$TMP/imagina-woo-quotes.zip.sha256")"
B_MD="$(git -C "$ROOT" hash-object -w "$TMP/README.md")"
TREE="$(printf '100644 blob %s\tREADME.md\n100644 blob %s\timagina-woo-quotes.zip\n100644 blob %s\timagina-woo-quotes.zip.sha256\n' "$B_MD" "$B_ZIP" "$B_SHA" | git -C "$ROOT" mktree)"

if [[ -n "$PARENT" ]]; then
	COMMIT="$(git -C "$ROOT" commit-tree "$TREE" -p "$PARENT" -m "Release $VERSION" -m "Zip instalable de la versión $VERSION. Detalle en CHANGELOG.md de la rama de código.")"
else
	COMMIT="$(git -C "$ROOT" commit-tree "$TREE" -m "Release $VERSION" -m "Zip instalable de la versión $VERSION. Detalle en CHANGELOG.md de la rama de código.")"
fi

git -C "$ROOT" update-ref refs/heads/release "$COMMIT"
rm -rf "$TMP"

echo "Rama release actualizada con la versión $VERSION ($SHA)."
echo "Publícala con: git push origin release"
