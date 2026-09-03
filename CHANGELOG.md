# Registro de cambios

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).
Versionado [semántico](https://semver.org/lang/es/): MAYOR.MENOR.PARCHE.

Los zips instalables de cada versión están en la rama `release`.

## [1.5.0] - 2026-09-03

### Añadido
- Ajustes «Reparto de las columnas» (lista 30, 40, 50, 60 o 70 % y el resto
  para el formulario) y «Separación entre columnas» en Presupuestos →
  Diseño → Página de solicitud. Hasta ahora el reparto era fijo

### Corregido
- El texto de privacidad del formulario mostraba «%s» en lugar del enlace a
  la política de privacidad: el valor por defecto usaba un marcador
  distinto del que entiende la plantilla. Las instalaciones que ya lo
  tienen guardado también se corrigen sin tocar el ajuste

## [1.4.0] - 2026-09-03

### Añadido
- Página de solicitud a dos columnas de verdad en escritorio: con la
  disposición en columnas la página pasa al ancho amplio del tema
  (`alignwide`), la lista ocupa cinco doceavos y el formulario siete (o al
  revés con el formulario a la izquierda) y la lista se queda fija mientras
  se rellena el formulario. En pantallas estrechas se apila
- Ajuste «Ancho en escritorio» (automático, contenido, amplio o completo) y
  «Fijar la lista al hacer scroll» en Presupuestos → Diseño
- El bloque «Lista de presupuesto» admite alineación ancha y completa desde
  el editor, y el shortcode `[iwq_quote_list width="wide"]` también; ambos
  mandan sobre el ajuste

## [1.3.0] - 2026-09-03

### Añadido
- Pestaña «Diseño» en WooCommerce → Presupuestos con vista previa en vivo
  del botón, el panel lateral y el formulario, que cambia al instante con
  cada ajuste sin guardar
- Colores del front: principal, principal al pasar el ratón, texto sobre el
  principal, texto, texto secundario, fondo, fondo secundario y bordes;
  redondeo general y modo oscuro automático opcional
- Botón «Solicitar presupuesto»: relleno o contorno, tipografía del tema o
  del sistema, tamaño de letra, grosor, mayúsculas, relleno vertical y
  horizontal, redondeo (hasta píldora), sombra y ancho completo en la ficha;
  color de los enlaces y al pasar el ratón
- Panel lateral: título, texto del botón inferior, lado (derecha o
  izquierda), ancho, cabecera con el color principal, opacidad del velo y
  miniaturas
- Página de solicitud: disposición en una columna o en dos (lista y
  formulario en cualquier orden), bloques sin fondo o como tarjetas con
  borde o sombra, título de la lista, miniaturas, estilo de los campos (con
  borde, rellenos o solo línea inferior) y su redondeo
- CSS adicional del front, saneado y impreso solo donde carga el plugin

### Cambiado
- Los ajustes de diseño se imprimen como variables CSS en línea tras la
  hoja del front: unas decenas de bytes y ningún archivo generado. Con los
  valores por defecto no se imprime nada
- El ajuste «Estilo» del botón pasa de la pestaña «Botones y catálogo» a la
  pestaña «Diseño»; su valor guardado se conserva
- El modo oscuro automático del front ya no viene activado: ahora es un
  ajuste, porque invertía los colores del panel en tiendas con tema claro

### Corregido
- Los campos de media fila del formulario se salían de su columna en temas
  que no aplican `box-sizing: border-box` a los campos

## [1.2.0] - 2026-09-03

### Añadido
- Panel de administración propio en WooCommerce → Presupuestos: cabecera con
  versión y acceso a pedidos, navegación lateral con iconos agrupada en
  «Panel» y «Ajustes», tarjetas por sección, barra de guardado fija con
  estado de cambios pendientes, atajo Ctrl/Cmd + S, aviso flotante al
  guardar y aviso al salir con cambios sin guardar
- Pestaña «Inicio»: solicitudes de los últimos 30 días, cuántas esperan
  respuesta, aceptados y valor aceptado; lista de puesta en marcha
  (sistema activo, página publicada, PDF disponible, plantilla elegida,
  aviso de nueva solicitud, protección contra spam) con enlace directo a
  lo que falta; accesos rápidos y actividad reciente con estado y total
- Estadísticas y vista previa rediseñadas sobre el mismo sistema: tarjetas
  de métricas, selector de periodo, tablas y controles propios

### Cambiado
- Los estilos y el script del panel (25 KB y 12 KB sin comprimir) se cargan
  solo en la pantalla del plugin. Pedidos y productos reciben una hoja
  aparte de un kilobyte para sus metaboxes y ningún JavaScript; el resto
  del admin no carga nada del plugin
- Los campos de ajustes se pintan en filas propias en lugar de `form-table`;
  el guardado sigue pasando por la API de ajustes de WordPress, con el
  mismo nonce, capacidad y saneado que antes
- Los desplegables llevan una flecha propia y los campos bloqueados del
  constructor de formularios se muestran atenuados en lugar de con el
  patrón rayado del navegador

### Corregido
- La pestaña «Formulario» abría todos los paneles del constructor al
  cargar; ahora se abren solo al pulsar «Editar»

## [1.1.0] - 2026-09-02

### Añadido
- Tres diseños de email seleccionables: «Moderno» (tarjeta con barra de
  color y botones grandes), «Minimalista» (texto sobre blanco) y «Como
  WooCommerce» (cabecera, pie y colores de la tienda). Con color de acento,
  logotipo y pie configurables en la pestaña Emails
- Los seis emails se reescriben sobre partes reutilizables: resumen del
  presupuesto, tabla de productos con miniaturas y precios tachados, botones
  de respuesta, datos del cliente, contraoferta y respuestas del formulario.
  Todas sobreescribibles desde el tema en `imagina-woo-quotes/emails/parts/`
- Pestaña «Vista previa» en WooCommerce → Presupuestos: cada email tal como
  lo recibe su destinatario (asunto, remitente, destinatario, adjuntos y
  estado del presupuesto), en HTML, texto plano y con el PDF adjunto;
  envío de prueba a cualquier dirección; y un botón para crear un
  presupuesto de ejemplo con descuento, validez y contraoferta cuando la
  tienda aún no tiene ninguno

### Cambiado
- La tabla de productos de los emails es propia del plugin: respeta los
  precios ocultos y muestra el precio de catálogo tachado cuando el
  presupuesto lo mejora

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

[1.5.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.5.0
[1.4.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.4.0
[1.3.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.3.0
[1.2.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.2.0
[1.1.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.1.0
[1.0.2]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.0.2
[1.0.1]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.0.1
[1.0.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.0.0
