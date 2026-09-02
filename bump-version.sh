#!/usr/bin/env bash
#
# Sube la versión del plugin en todos los sitios donde vive.
#
# Uso: ./bump-version.sh 1.1.0
#
# Actualiza la cabecera del plugin, la constante IWQ_VERSION y abre una
# sección nueva en el CHANGELOG. No hace commit: revisa el diff y confirma.

set -euo pipefail

VERSION="${1:-}"
[[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "Uso: $0 MAYOR.MENOR.PARCHE" >&2; exit 1; }

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN="$ROOT/imagina-woo-quotes/imagina-woo-quotes.php"
CURRENT="$(grep -m1 "^ \* Version:" "$MAIN" | awk '{print $3}')"

sed -i "s/^ \* Version: .*/ * Version:     $VERSION/" "$MAIN"
sed -i "s/define( 'IWQ_VERSION', '[^']*' );/define( 'IWQ_VERSION', '$VERSION' );/" "$MAIN"

TODAY="$(date +%Y-%m-%d)"
python3 - "$ROOT/CHANGELOG.md" "$VERSION" "$TODAY" "$CURRENT" <<'PY'
import sys, re
path, version, today, current = sys.argv[1:]
s = open(path).read()
if f"## [{version}]" in s:
    sys.exit(0)
section = f"## [{version}] - {today}\n\n### Añadido\n- \n\n### Corregido\n- \n\n"
s = s.replace("## [", section + "## [", 1)
s = s.replace(f"[{current}]: ", f"[{version}]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v{version}\n[{current}]: ", 1)
open(path, "w").write(s)
PY

echo "Versión: $CURRENT → $VERSION"
echo "Revisa el CHANGELOG, haz commit y etiqueta con: git tag -a v$VERSION -m \"v$VERSION\""
