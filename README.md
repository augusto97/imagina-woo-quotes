# Imagina Woo Quotes

**Versión actual: 1.11.7** · 2026-09-08 · 4.7M

Esta rama contiene únicamente el zip instalable de la última versión, con
nombre fijo, para que siempre sepas cuál descargar:

- [`imagina-woo-quotes.zip`](imagina-woo-quotes.zip)
- SHA-256: `f12e436c51fb5fb114782f008fa375aaa1e34025388e4bd2cfaa25580578d6a2`

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
