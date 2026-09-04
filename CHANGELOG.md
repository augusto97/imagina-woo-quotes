# Registro de cambios

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).
Versionado [semántico](https://semver.org/lang/es/): MAYOR.MENOR.PARCHE.

El zip instalable de la última versión está en la rama `release`, siempre
con el nombre `imagina-woo-quotes.zip`.

## [1.11.3] - 2026-09-04

### Corregido
- Un interruptor de los ajustes podía verse apagado mientras la función
  estaba activa (por ejemplo «Activar los presupuestos» apagado con los
  botones visibles en la tienda): la página leía la opción en bruto y el
  front aplicaba su valor por defecto cuando nunca se había guardado. Ahora
  hay una sola lista de valores por defecto que usan la instalación, el
  front y la página de ajustes
- Si el plugin se actualizaba o se instalaba sin pasar por la activación
  (subida por FTP, actualizadores que activan en silencio), las opciones
  nuevas, las capacidades y la carpeta de adjuntos no se creaban. Ahora se
  completan solos la primera vez que carga cada versión
- El aviso «Connection lost. Saving has been disabled until you are
  reconnected» de WooCommerce ya no aparece en la pantalla de ajustes del
  plugin: los ajustes se guardan con un envío normal del formulario y no
  dependen del latido de WordPress

## [1.11.2] - 2026-09-04

### Corregido
- En dos columnas, si la columna de la lista era más estrecha que la tabla
  de productos, la tabla se recortaba y dejaba fuera el subtotal. Ahora la
  columna mide su propio ancho y, cuando no cabe la tabla completa, cada
  línea pasa al formato apilado que WooCommerce usa en el móvil (etiqueta:
  valor), como hace el propio carrito

## [1.11.1] - 2026-09-04

### Corregido
- Con la disposición en dos columnas, si el tema no daba al contenido el
  ancho amplio, la tabla de productos se salía de su columna y se montaba
  sobre el formulario. La página ahora mide su propio ancho: por debajo de
  760 px apila las columnas y la tabla se desplaza dentro de su columna en
  vez de desbordarla

## [1.11.0] - 2026-09-04

### Añadido
- Ajuste «Fecha de la venta» en Presupuestos → Presupuestos → Informes:
  cuando un presupuesto aceptado se paga, el pedido puede fecharse en el
  momento del pago (recomendado, por defecto) o conservar la fecha de la
  solicitud. La fecha original queda anotada en el pedido
- Ajuste «Ocultar los presupuestos de la vista "Todos" de Pedidos»: los
  presupuestos quedan solo en sus filtros por estado, con y sin HPOS

### Corregido
- Un presupuesto aceptado que se pagaba con una pasarela que completa el
  pago automáticamente (tarjeta, PayPal…) se quedaba en «Presupuesto
  aceptado» en vez de pasar a «Procesando» o «Completado», porque
  WooCommerce solo completa el pago desde sus propios estados. Ahora
  «Aceptado» es un estado válido para completar el pago
- WooCommerce Analytics contaba los presupuestos (solicitados, enviados,
  aceptados sin pagar, rechazados, vencidos y de ejemplo) en ingresos,
  pedidos, productos y clientes. Ahora los cinco estados quedan excluidos,
  también en el histórico; un presupuesto solo cuenta cuando se paga y pasa
  a un estado de venta

## [1.10.0] - 2026-09-04

### Añadido
- Los bloques de las plantillas de PDF se ven en el editor tal como
  saldrán en el documento: el servidor los pinta con el mismo código que
  genera el PDF, con los estilos del documento, sobre el presupuesto de
  ejemplo, el último presupuesto enviado o, si la tienda no tiene ninguno,
  datos de muestra en memoria. Una etiqueta al pasar el ratón recuerda que
  son datos de ejemplo. Los bloques que no tienen nada que pintar, como el
  logotipo sin configurar, siguen mostrando su explicación

## [1.9.6] - 2026-09-04

### Corregido
- Vista previa: la pestaña «PDF adjunto» salía en blanco en Chrome con el
  aviso «Chrome bloqueó esta página», porque el visor de PDF no funciona
  dentro de un iframe con sandbox. El aislamiento se aplica solo a las
  vistas de email
- Vista previa: «Crear presupuesto de ejemplo» parecía no hacer nada cuando
  la petición fallaba, porque el error solo se escribía en la tarjeta de
  envío de prueba y una respuesta de servidor con error no se mostraba.
  Ahora el resultado, bueno o malo, aparece junto al botón y como aviso
  flotante, y los errores del servidor al crear el ejemplo se devuelven con
  su mensaje

## [1.9.5] - 2026-09-04

### Corregido
- WooCommerce marcaba el plugin como incompatible con sus características
  activas en las tiendas con el editor de productos nuevo (experimental,
  retirado en WooCommerce 11), porque no declaraba compatibilidad con él.
  Ya la declara, junto a HPOS y los bloques de carrito y finalizar compra.
  El ajuste «Presupuestos» de cada producto sigue en el editor clásico

## [1.9.4] - 2026-09-03

### Añadido
- Fila de total al final de la lista de la página de solicitud, en la tabla
  tipo carrito y en la lista compacta. Se recalcula al cambiar cantidades o
  quitar productos y desaparece si ningún producto muestra precio

## [1.9.3] - 2026-09-03

### Corregido
- En la página de solicitud el subtotal no cambiaba al tocar la cantidad en
  temas que sustituyen la caja por su propio control de más y menos y
  anuncian el cambio con jQuery: ese evento no llega a los oyentes nativos.
  Las cantidades se escuchan ahora también por jQuery y al escribir

## [1.9.2] - 2026-09-03

### Corregido
- En la densidad compacta del panel lateral, la celda del producto dejaba
  de ser celda de tabla y cada columna cerraba a su propia altura, con el
  separador partido en tres trozos. La disposición compacta va ahora en un
  bloque interno y las tres celdas comparten la altura de la fila

## [1.9.1] - 2026-09-03

### Corregido
- El separador entre filas del panel lateral se cortaba antes de la columna
  del total en temas que quitan el borde a la última celda de las tablas.
  Ahora se dibuja como sombra interior en cada celda, sin depender de los
  bordes de tabla del tema

## [1.9.0] - 2026-09-03

### Añadido
- Ajustes del panel lateral en Presupuestos → Diseño: título y su tamaño,
  contador de productos opcional, densidad de las filas (compacta o como el
  mini carrito), tamaño de letra de las filas, subtotal opcional con nota
  editable, textos de los dos botones, botón de seguir comprando opcional,
  botones en columna o en fila, tamaño de letra y altura de los botones y
  colores de fondo, texto y borde de cada botón

### Cambiado
- El panel es más compacto por defecto: título a 20 px, filas con precio y
  cantidad en la misma línea, miniatura a 48 px y botones uno debajo del
  otro a ancho completo, para que no partan el texto en varias líneas

## [1.8.0] - 2026-09-03

### Añadido
- Panel lateral rehecho con la disposición del mini carrito de WooCommerce:
  título con contador de productos, filas con imagen, nombre, precio,
  atributos, control de cantidad con más y menos, icono de quitar y total de
  línea; pie con subtotal de catálogo y dos botones del tema (contorno para
  seguir viendo productos, relleno para ver y enviar la solicitud); estado
  vacío con enlace al catálogo. Ancho por defecto 480 px, como el mini
  carrito
- El contador del título, el subtotal y los totales de línea se actualizan
  al añadir, cambiar cantidades o quitar, sin recargar

### Cambiado
- Los botones del panel usan las clases de botón del tema, como el carrito

## [1.7.0] - 2026-09-03

### Añadido
- Controles de miniatura en Presupuestos → Diseño: tamaño y redondeo de la
  imagen en la página de solicitud (tabla y lista compacta) y tamaño en el
  panel lateral. Por defecto 64 px

### Corregido
- La miniatura de la tabla de la página de solicitud salía a 300 px en los
  temas que solo limitan la imagen dentro del formulario del carrito

## [1.6.0] - 2026-09-03

### Añadido
- La lista de productos de la página de solicitud se pinta con la misma
  tabla que el carrito de WooCommerce (`shop_table cart`): mismas columnas
  (quitar, imagen, producto, precio, cantidad, subtotal), misma caja de
  cantidad de `woocommerce_quantity_input()`, mismo aspa de quitar y botones
  con la clase `button` del tema. El tema la pinta idéntica al carrito, sin
  CSS propio. El subtotal de cada línea se actualiza al cambiar la cantidad
- Ajuste «Lista de productos» en Presupuestos → Diseño → Página de
  solicitud: «Como el carrito de WooCommerce» (por defecto) o «Lista
  compacta del plugin», la de antes, con los colores de la pestaña
- Con la tabla del carrito, los botones de enviar, vaciar y seguir viendo
  productos usan las clases de botón del tema (`button`, `button alt`)

### Cambiado
- Las columnas de precio y subtotal se omiten cuando todos los productos de
  la lista tienen el precio oculto

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

[1.11.3]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.11.3
[1.11.2]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.11.2
[1.11.1]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.11.1
[1.11.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.11.0
[1.10.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.10.0
[1.9.6]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.9.6
[1.9.5]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.9.5
[1.9.4]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.9.4
[1.9.3]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.9.3
[1.9.2]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.9.2
[1.9.1]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.9.1
[1.9.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.9.0
[1.8.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.8.0
[1.7.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.7.0
[1.6.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.6.0
[1.5.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.5.0
[1.4.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.4.0
[1.3.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.3.0
[1.2.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.2.0
[1.1.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.1.0
[1.0.2]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.0.2
[1.0.1]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.0.1
[1.0.0]: https://github.com/augusto97/imagina-woo-quotes/releases/tag/v1.0.0
