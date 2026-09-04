# Imagina Woo Quotes

**Versión actual: 1.9.4** · 2026-09-04 · 4.7M

Esta rama contiene únicamente el zip instalable de la última versión, con
nombre fijo, para que siempre sepas cuál descargar:

- [`imagina-woo-quotes.zip`](imagina-woo-quotes.zip)
- SHA-256: `d2c73f072dda0f69df15b9cb0eeaa68e2d1bc9099fb0f637070b7a8db80541d4`

## Instalación

En WordPress: **Plugins → Añadir nuevo → Subir plugin**, elige el zip y activa.
Para actualizar, sube el zip nuevo con «Reemplazar el actual». Los ajustes y
los presupuestos se conservan.

Con WP-CLI:

```bash
wp plugin install imagina-woo-quotes.zip --activate   # o --force para actualizar
```

Verificar la descarga:

```bash
sha256sum -c imagina-woo-quotes.zip.sha256
```

Requisitos: WordPress 6.4+, WooCommerce 8.0+, PHP 8.1+. El zip incluye dompdf:
no hace falta Composer en el servidor.

El historial de cambios está en `CHANGELOG.md` de la rama de código.
