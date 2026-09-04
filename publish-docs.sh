#!/usr/bin/env bash
#
# Publica el manual de uso en la rama `docs`, siempre actualizado y listo
# para descargar.
#
# La rama contiene:
#   index.html                       el manual, con las capturas en images/
#   images/                          capturas de pantalla
#   imagina-woo-quotes-manual.html   el manual en un solo archivo (imágenes
#                                    incrustadas), para enviarlo o abrirlo sin más
#   imagina-woo-quotes-manual.zip    index.html + images/, para subirlo a un servidor
#   README.md
#
# Cada publicación reemplaza el contenido anterior; el manual se edita en
# docs/manual.src.html de la rama de código y se reconstruye aquí.
#
# Uso: ./publish-docs.sh    y después    git push origin docs

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN="$ROOT/imagina-woo-quotes/imagina-woo-quotes.php"
VERSION="$(grep -m1 "^ \* Version:" "$MAIN" | awk '{print $3}')"

[[ -z "$(git -C "$ROOT" status --porcelain)" ]] || { echo "Hay cambios sin confirmar: haz commit antes de publicar." >&2; exit 1; }

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

python3 "$ROOT/docs/build-manual.py" --single "$TMP/imagina-woo-quotes-manual.html" >/dev/null
[[ -z "$(git -C "$ROOT" status --porcelain)" ]] || { echo "docs/manual.html no estaba al día: haz commit del resultado y vuelve a publicar." >&2; exit 1; }

cp "$ROOT/docs/manual.html" "$TMP/index.html"
cp -r "$ROOT/docs/images" "$TMP/images"
( cd "$TMP" && zip -q -r -X imagina-woo-quotes-manual.zip index.html images )
SIZE_ZIP="$(du -h "$TMP/imagina-woo-quotes-manual.zip" | cut -f1)"
SIZE_ONE="$(du -h "$TMP/imagina-woo-quotes-manual.html" | cut -f1)"

cat > "$TMP/README.md" <<README
# Manual de uso de Imagina Woo Quotes

**Corresponde a la versión $VERSION del plugin** · $(date +%Y-%m-%d)

Esta rama contiene únicamente el manual de uso, siempre en su última versión:

- [\`imagina-woo-quotes-manual.html\`](imagina-woo-quotes-manual.html) ($SIZE_ONE):
  el manual completo en un solo archivo, con las capturas incrustadas. Es el
  que conviene descargar o enviar a un cliente: se abre en cualquier navegador.
- [\`imagina-woo-quotes-manual.zip\`](imagina-woo-quotes-manual.zip) ($SIZE_ZIP):
  \`index.html\` más la carpeta \`images/\`, para subirlo a un servidor web.
- [\`index.html\`](index.html) e [\`images/\`](images/): el mismo contenido sin
  comprimir. Con GitHub Pages apuntando a esta rama, el manual queda publicado
  en línea.

El manual se edita en \`docs/manual.src.html\` de la rama de código y se
publica aquí con \`./publish-docs.sh\`. El zip instalable del plugin está en la
rama \`release\`.
README

# El commit se construye con un índice temporal, sin tocar el árbol de
# trabajo, encadenado a la última publicación (si existe).
git -C "$ROOT" fetch -q origin docs 2>/dev/null || true
PARENT="$(git -C "$ROOT" rev-parse -q --verify refs/heads/docs 2>/dev/null || true)"

export GIT_INDEX_FILE="$TMP/index"
git -C "$ROOT" --work-tree="$TMP" add -A -- README.md index.html images imagina-woo-quotes-manual.html imagina-woo-quotes-manual.zip
TREE="$(git -C "$ROOT" write-tree)"
unset GIT_INDEX_FILE

MSG="Manual para la versión $VERSION"
if [[ -n "$PARENT" ]]; then
	COMMIT="$(git -C "$ROOT" commit-tree "$TREE" -p "$PARENT" -m "$MSG")"
else
	COMMIT="$(git -C "$ROOT" commit-tree "$TREE" -m "$MSG")"
fi
git -C "$ROOT" update-ref refs/heads/docs "$COMMIT"

echo "Rama docs actualizada con el manual de la versión $VERSION."
echo "Publícala con: git push origin docs"
