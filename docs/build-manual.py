#!/usr/bin/env python3
"""Construye el manual a partir de docs/manual.src.html.

Salidas:
  docs/manual.html            imágenes relativas en docs/images/ (siempre)
  --single RUTA               documento completo con las imágenes incrustadas
  --artifact RUTA             fragmento sin <html>/<head> con imágenes incrustadas

La versión que aparece en el manual se toma de la cabecera del plugin."""
import base64, os, re, struct, sys

root = os.path.dirname(os.path.abspath(__file__))
src = open(os.path.join(root, 'manual.src.html'), encoding='utf-8').read()
main = open(os.path.join(root, '..', 'imagina-woo-quotes', 'imagina-woo-quotes.php'), encoding='utf-8').read()
version = re.search(r'^ \* Version:\s*(\S+)', main, re.M).group(1)
src = src.replace('{{version}}', version)

def size(name):
    # Cabecera PNG: ancho y alto en los bytes 16-24. Con ellos el navegador
    # reserva el hueco antes de cargar la imagen y las anclas no se mueven.
    with open(os.path.join(root, 'images', name + '.png'), 'rb') as f:
        f.seek(16)
        return struct.unpack('>II', f.read(8))

IMG = r'<img src="\{\{img:([a-z0-9-]+)\}\}"'

def rel(m):
    w, h = size(m.group(1))
    return '<img src="images/%s.png" width="%d" height="%d"' % (m.group(1), w, h)

def inline(m):
    w, h = size(m.group(1))
    path = os.path.join(root, 'images', m.group(1) + '.png')
    data = base64.b64encode(open(path, 'rb').read()).decode('ascii')
    return '<img src="data:image/png;base64,%s" width="%d" height="%d"' % (data, w, h)

def write(path, text):
    open(path, 'w', encoding='utf-8').write(text)
    print('generado', path)

write(os.path.join(root, 'manual.html'), re.sub(IMG, rel, src))

args = sys.argv[1:]
inlined = None
while args:
    flag, path = args[0], args[1]
    args = args[2:]
    if inlined is None:
        inlined = re.sub(IMG, inline, src)
    if flag == '--single':
        write(path, inlined)
    elif flag == '--artifact':
        # La plataforma de artefactos pone su propio envoltorio y sus metas.
        frag = inlined.split('<body>', 1)[1].rsplit('</body>', 1)[0]
        head = inlined.split('<head>', 1)[1].split('</head>', 1)[0]
        head = re.sub(r'<meta[^>]+>\s*', '', head)
        write(path, head.strip() + '\n' + frag)
    else:
        sys.exit('opción desconocida: ' + flag)
