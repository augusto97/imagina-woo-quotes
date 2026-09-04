#!/usr/bin/env python3
"""Construye docs/manual.html (imágenes relativas) y una variante con las
imágenes incrustadas como data URI, a partir de docs/manual.src.html."""
import base64, os, re, sys
root = os.path.dirname(os.path.abspath(__file__))
src = open(os.path.join(root, 'manual.src.html'), encoding='utf-8').read()
def rel(m):
    return 'images/' + m.group(1) + '.png'
def inline(m):
    path = os.path.join(root, 'images', m.group(1) + '.png')
    data = base64.b64encode(open(path, 'rb').read()).decode('ascii')
    return 'data:image/png;base64,' + data
open(os.path.join(root, 'manual.html'), 'w', encoding='utf-8').write(re.sub(r'\{\{img:([a-z0-9-]+)\}\}', rel, src))
out = sys.argv[1] if len(sys.argv) > 1 else os.path.join(root, 'manual-inline.html')
inlined = re.sub(r'\{\{img:([a-z0-9-]+)\}\}', inline, src)
# La versión para el artefacto no lleva envoltorio: la plataforma lo añade.
frag = inlined.split('<body>', 1)[1].rsplit('</body>', 1)[0]
head = inlined.split('<head>', 1)[1].split('</head>', 1)[0]
head = re.sub(r'<meta[^>]+>\s*', '', head)
open(out, 'w', encoding='utf-8').write(head.strip() + '\n' + frag)
print('manual.html y', out, 'generados')
