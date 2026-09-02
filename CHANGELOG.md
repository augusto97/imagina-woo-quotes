# Registro de cambios

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).
Versionado [semántico](https://semver.org/lang/es/): MAYOR.MENOR.PARCHE.

Los zips instalables de cada versión están en la rama `release`.

## [1.0.2] - 2026-09-02

### Cambiado
- La solicitud nace con los precios de catálogo (impuestos según la
  configuración de la tienda) en vez de a cero: el pedido, el email y el PDF
  muestran importes reales desde el principio y el administrador solo ajusta
  lo que quiera descontar. El PDF de una solicitud sin valorar los marca
  como «precios de catálogo, orientativos»
- Si al cliente se le ocultaban los precios en el catálogo, la solicitud
  tampoco los muestra en el email, el PDF ni Mi Cuenta hasta que la tienda la
  valora y envía. El administrador sí los ve en el pedido
- El panel lateral y la página de solicitud muestran el precio de referencia
  de cada línea cuando el cliente puede verlo
- La plantilla de PDF por defecto ya no dice «válido hasta el —» en una
  solicitud sin fecha; la validez la pinta el bloque de datos solo cuando
  existe. Las plantillas ya creadas no cambian: edita el último párrafo si
  te afecta

## [1.0.1] - 2026-09-02

### Corregido
- El PDF salía casi vacío (solo título y pie) cuando se generaba desde el
  enlace de Mi Cuenta o desde un email: el endpoint corría en `init` antes de
  que se registraran los bloques de la plantilla, y ese documento vacío
  quedaba cacheado. Ahora se sirve en `template_redirect`, la generación
  registra los bloques si hace falta, y un PDF generado por otra versión del
  plugin o con una plantilla editada después se regenera solo
- Los emails de solicitud nueva (administrador y cliente) y el recordatorio
  no llevaban el PDF adjunto; solo lo llevaba el de presupuesto listo. Ahora
  lo llevan los cuatro, y el filtro `iwq_emails_with_pdf` permite elegirlos

### Cambiado
- Un presupuesto todavía sin valorar se documenta como resguardo: la tabla
  no muestra columnas de precio a cero y los totales se sustituyen por
  «Precios pendientes de valoración»

## [1.0.0] - 2026-09-02

Primera versión.

### Añadido
- Lista de presupuesto con panel lateral accesible, sin jQuery, con sesión de
  WooCommerce diferida hasta el primer añadido para no invalidar la caché
- Cinco estados de pedido: solicitado, enviado, aceptado, rechazado, vencido
- Constructor de formularios con 16 tipos de campo, adjuntos protegidos y
  reCAPTCHA v2/v3
- Seis emails `WC_Email` configurables desde WooCommerce
- PDF con dompdf y plantillas editables en el editor de bloques, con siete
  bloques propios y marcadores de texto
- Contraoferta del cliente con hilo de conversación en Mi Cuenta y en el pedido
- Reglas por producto, categoría, etiqueta, rol, stock y modo por producto
- Vencimiento automático, recordatorios y limpieza por cron
- Pasarela «Solicitar presupuesto» para el checkout clásico y de bloques
- Estadísticas y contador de solicitudes por producto
- Botón para pasar el carrito completo a presupuesto

### Verificado
- WordPress 6.9.7, WooCommerce 10.9.0, PHP 8.4, tema Twenty Twenty-Five
- 27 comprobaciones de navegador con Playwright sobre Chromium

[1.0.2]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.0.2
[1.0.1]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.0.1
[1.0.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.0.0
