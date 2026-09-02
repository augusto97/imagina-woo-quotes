# Imagina Woo Quotes

Sistema de solicitud de presupuestos para WooCommerce. El cliente arma una lista
de productos, envía una solicitud, la tienda la valora y el cliente acepta,
rechaza o **contraoferta**.

## Por qué otro plugin de presupuestos

Este repositorio nació analizando dos plugins comerciales del sector
(YITH Request a Quote y WPC Request a Quote). La conclusión fue que el problema
no es la falta de funciones, sino el peso: los plugins de esta categoría tienen
fama de ralentizar las tiendas, y con razón.

| | YITH | WPC | Este plugin |
|---|---|---|---|
| Framework de admin | `plugin-fw`, 18 MB, en todo el admin | — | API nativa de WordPress |
| jQuery en el front | jQuery + jQuery UI + dialog | jQuery | **ninguno** |
| Librería de gráficos | — | Chart.js, 208 KB | SVG generado en PHP |
| Sesión de WooCommerce | se abre a todo visitante | ídem | **solo al primer añadido** |
| Assets | globales | globales | solo donde hay botón |

El renglón de la sesión es el importante. Abrir la sesión de WooCommerce para
cada visitante anónimo pone una cookie que **invalida la caché de página
completa del sitio entero**. Aquí no se abre ninguna sesión hasta que alguien
añade un producto de verdad, y el contador de la cabecera se hidrata desde
`localStorage`, de modo que funciona sobre páginas cacheadas sin una sola
petición al servidor.

Resultado: **~19 KB de JavaScript y ~12 KB de CSS sin minificar**, cargados solo
en las páginas que los necesitan.

## Funciones

**Catálogo y botón**
- Botón en ficha de producto, catálogo, carrito, shortcodes y bloques
- Ocultar precio y botón de compra, globalmente o por rol de usuario
- Reglas por producto, categoría, etiqueta, estado de stock y tipo de producto
- Ajuste por producto: heredar, permitir, solo presupuesto, o nunca

**Solicitud**
- Panel lateral accesible con trampa de foco, `Escape` y avisos por `aria-live`
- Constructor de formularios con 16 tipos de campo, reordenables arrastrando
- Los campos se enlazan a los datos de facturación del pedido en vez de quedar
  como metadatos sueltos
- Adjuntos guardados fuera del alcance del navegador, servidos por un endpoint
  que comprueba permisos y verifica que el contenido coincida con la extensión
- reCAPTCHA v2 y v3, con el script de Google cargado solo donde hace falta
- Límite de solicitudes por IP

**Presupuesto**
- Cinco estados de pedido de WooCommerce: solicitado, enviado, aceptado,
  rechazado, vencido
- Los presupuestos son pedidos reales, así que heredan HPOS, notas, informes y
  reembolsos sin reimplementar nada
- Vencimiento automático con recordatorio previo
- **Contraoferta del cliente** con hilo de conversación en ambos lados
- Pasarela opcional para solicitar presupuesto desde el checkout

**PDF**
- Plantillas editables con el editor de bloques, con siete bloques propios que
  se renderizan en servidor con el pedido en contexto
- Marcadores de texto: `{order_number}`, `{expiry_date}`, `{accept_url}`…
- dompdf se carga solo al generar un documento y el resultado se cachea

**Emails**
- Seis clases `WC_Email`, configurables desde WooCommerce → Ajustes → Emails
- Tres diseños seleccionables (moderno, minimalista, como WooCommerce) con
  color de acento, logotipo y pie propios
- Plantillas HTML y de texto plano, sobreescribibles desde el tema; el
  contenido se compone de partes reutilizables en `templates/emails/parts/`
- Vista previa en el admin de cada email y del PDF adjunto, tal como los
  recibe el cliente o el administrador, con envío de prueba

**Datos**
- Estadísticas: tasa de aceptación y de respuesta, valor aceptado, reparto por
  estado
- Contador de solicitudes por producto, con columna ordenable en el listado

## Requisitos

- WordPress 6.4 o superior
- WooCommerce 8.0 o superior
- PHP 8.1 o superior

## Instalación

Probado en WordPress 6.9.7 con WooCommerce 10.9.0 sobre PHP 8.4, en un tema de
bloques (Twenty Twenty-Five).

```bash
git clone https://github.com/augusto97/imagina-woo-quotes.git
cd imagina-woo-quotes/imagina-woo-quotes
composer install --no-dev --optimize-autoloader
```

El plugin funciona sin `composer install`; lo único que no estará disponible es
la generación de PDF, y los ajustes lo avisan.

Para generar un zip instalable:

```bash
./build.sh
```

## Personalización

**Plantillas.** Cópialas a tu tema bajo `imagina-woo-quotes/`, respetando la
ruta. Por ejemplo, `templates/quote/drawer.php` se sobreescribe en
`tu-tema/imagina-woo-quotes/quote/drawer.php`.

**Estilos.** Todo el CSS del front usa variables. Para cambiar el color de los
botones basta con:

```css
:root {
	--iwq-accent: #e11d48;
	--iwq-accent-hover: #be123c;
}
```

**Código.** El plugin expone filtros y acciones en todos los puntos de
extensión. Los más útiles:

| Hook | Para qué |
|---|---|
| `iwq_is_quotable` | Decidir si un producto admite presupuesto |
| `iwq_form_fields` | Añadir o quitar campos del formulario en tiempo de ejecución |
| `iwq_validate_form` | Validaciones propias |
| `iwq_request_created` | Actuar cuando llega una solicitud |
| `iwq_quote_accepted` / `iwq_quote_rejected` | Reaccionar a la respuesta del cliente |
| `iwq_counter_offer_received` | Reaccionar a una contraoferta |
| `iwq_pdf_html` | Modificar el HTML del PDF antes de renderizarlo |
| `iwq_enqueue_assets` | Forzar la carga de assets en una página concreta |

## Estructura

```
imagina-woo-quotes/
├── imagina-woo-quotes.php     Archivo principal
├── includes/
│   ├── class-iwq.php          Contenedor y arranque de módulos
│   ├── class-iwq-quote.php    Máquina de estados del presupuesto
│   ├── class-iwq-session.php  Lista del visitante
│   ├── admin/                 Ajustes, estadísticas, paneles
│   ├── emails/                Seis clases WC_Email
│   ├── forms/                 Render, validación y reCAPTCHA
│   └── pdf/                   Generación y bloques del documento
├── templates/                 Todas sobreescribibles desde el tema
├── assets/                    CSS y JS del front y del admin
└── blocks/                    Bloques del editor, sin paso de compilación
```

## Licencia

GPL-3.0-or-later.
