<?php
/**
 * Render en servidor de los bloques de la plantilla de PDF.
 *
 * Cada bloque se pinta con el pedido que hay en contexto en el momento de
 * generar el documento, de modo que una misma plantilla sirve para todos los
 * presupuestos.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_PDF_Blocks
 */
class IWQ_PDF_Blocks {

	/**
	 * Presupuesto que se está renderizando.
	 *
	 * @var IWQ_Quote|null
	 */
	private static $context = null;

	/**
	 * Fija el presupuesto en contexto.
	 *
	 * @param IWQ_Quote|null $quote Presupuesto.
	 * @return void
	 */
	public static function set_context( $quote ) {
		self::$context = $quote;
	}

	/**
	 * Devuelve el presupuesto en contexto.
	 *
	 * @return IWQ_Quote|null
	 */
	private static function quote() {
		return self::$context;
	}

	/**
	 * Aviso que se muestra en el editor cuando no hay pedido en contexto.
	 *
	 * @param string $label Nombre del bloque.
	 * @return string
	 */
	private static function placeholder( $label ) {
		return sprintf(
			'<div class="iwq-pdf-placeholder">%s</div>',
			esc_html(
				sprintf(
					/* translators: %s: nombre del bloque. */
					__( '[%s: se rellena al generar el PDF]', 'imagina-woo-quotes' ),
					$label
				)
			)
		);
	}

	/**
	 * Bloque: tabla de productos del presupuesto.
	 *
	 * @param array $attributes Atributos del bloque.
	 * @return string
	 */
	public static function render_quote_table( $attributes = array() ) {
		$quote = self::quote();

		if ( ! $quote ) {
			return self::placeholder( __( 'Tabla de productos', 'imagina-woo-quotes' ) );
		}

		$order       = $quote->get_order();
		$show_images = ! empty( $attributes['showImages'] );
		$show_sku    = ! empty( $attributes['showSku'] );

		// Antes de valorar, el documento es un resguardo de lo pedido: sin
		// columnas de precio, que saldrían a cero.
		$priced = self::is_priced( $order );

		$html  = '<table class="iwq-pdf-table"><thead><tr>';
		$html .= '<th class="iwq-pdf-table__product">' . esc_html__( 'Producto', 'imagina-woo-quotes' ) . '</th>';
		$html .= '<th class="iwq-pdf-table__qty">' . esc_html__( 'Cantidad', 'imagina-woo-quotes' ) . '</th>';

		if ( $priced ) {
			$html .= '<th class="iwq-pdf-table__price">' . esc_html__( 'Precio', 'imagina-woo-quotes' ) . '</th>';
			$html .= '<th class="iwq-pdf-table__total">' . esc_html__( 'Total', 'imagina-woo-quotes' ) . '</th>';
		}

		$html .= '</tr></thead><tbody>';

		foreach ( $order->get_items() as $item_id => $item ) {
			$product    = $item->get_product();
			$quantity   = $item->get_quantity();
			$line_total = (float) $item->get_total();
			$unit       = $quantity ? $line_total / $quantity : 0;

			$html .= '<tr><td class="iwq-pdf-table__product">';

			if ( $show_images && $product ) {
				$image = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' );

				if ( $image ) {
					$html .= sprintf( '<img src="%s" class="iwq-pdf-thumb" alt="">', esc_url( $image ) );
				}
			}

			$html .= '<span class="iwq-pdf-table__name">' . esc_html( $item->get_name() ) . '</span>';

			if ( $show_sku && $product && $product->get_sku() ) {
				$html .= '<span class="iwq-pdf-table__sku">' . esc_html( $product->get_sku() ) . '</span>';
			}

			$html .= wp_kses_post( wc_display_item_meta( $item, array( 'echo' => false ) ) );
			$html .= '</td>';

			$html .= '<td class="iwq-pdf-table__qty">' . esc_html( $quantity ) . '</td>';

			if ( $priced ) {
				$html .= '<td class="iwq-pdf-table__price">' . self::price( $unit, $order, $quote, $item_id ) . '</td>';
				$html .= '<td class="iwq-pdf-table__total">' . wp_kses_post( wc_price( $line_total, array( 'currency' => $order->get_currency() ) ) ) . '</td>';
			}

			$html .= '</tr>';
		}

		return $html . '</tbody></table>';
	}

	/**
	 * Indica si el presupuesto ya tiene precios puestos por la tienda.
	 *
	 * @param WC_Order $order Pedido.
	 * @return bool
	 */
	private static function is_priced( $order ) {
		return 'iwq-new' !== $order->get_status() && (float) $order->get_total() > 0;
	}

	/**
	 * Pinta un precio, tachando el de catálogo si el presupuesto mejora.
	 *
	 * @param float     $unit    Precio unitario presupuestado.
	 * @param WC_Order  $order   Pedido.
	 * @param IWQ_Quote $quote   Presupuesto.
	 * @param int       $item_id ID de la línea.
	 * @return string
	 */
	private static function price( $unit, $order, $quote, $item_id ) {
		$args   = array( 'currency' => $order->get_currency() );
		$actual = wc_price( $unit, $args );

		if ( ! iwq_option_enabled( 'pdf_show_strikethrough', true ) ) {
			return wp_kses_post( $actual );
		}

		$list = $quote->get_list_price( $item_id );

		// Solo tiene sentido tachar si el presupuesto realmente mejora el
		// precio de catálogo.
		if ( ! $list || $list <= $unit ) {
			return wp_kses_post( $actual );
		}

		return '<del>' . wp_kses_post( wc_price( $list, $args ) ) . '</del> ' . wp_kses_post( $actual );
	}

	/**
	 * Bloque: totales del presupuesto.
	 *
	 * @return string
	 */
	public static function render_quote_totals() {
		$quote = self::quote();

		if ( ! $quote ) {
			return self::placeholder( __( 'Totales', 'imagina-woo-quotes' ) );
		}

		$order = $quote->get_order();

		if ( ! self::is_priced( $order ) ) {
			return '<p class="iwq-pdf-pending">' . esc_html__( 'Precios pendientes de valoración. Te enviaremos el presupuesto en breve.', 'imagina-woo-quotes' ) . '</p>';
		}

		$html = '<table class="iwq-pdf-totals">';

		foreach ( $order->get_order_item_totals() as $total ) {
			$html .= sprintf(
				'<tr><th>%s</th><td>%s</td></tr>',
				esc_html( $total['label'] ),
				wp_kses_post( $total['value'] )
			);
		}

		return $html . '</table>';
	}

	/**
	 * Bloque: datos del cliente.
	 *
	 * @return string
	 */
	public static function render_customer_info() {
		$quote = self::quote();

		if ( ! $quote ) {
			return self::placeholder( __( 'Datos del cliente', 'imagina-woo-quotes' ) );
		}

		$order   = $quote->get_order();
		$address = $order->get_formatted_billing_address();

		$html  = '<div class="iwq-pdf-customer">';
		$html .= '<h3>' . esc_html__( 'Cliente', 'imagina-woo-quotes' ) . '</h3>';
		$html .= '<p>' . wp_kses_post( $address ? $address : $order->get_formatted_billing_full_name() ) . '</p>';

		if ( $order->get_billing_email() ) {
			$html .= '<p>' . esc_html( $order->get_billing_email() ) . '</p>';
		}

		if ( $order->get_billing_phone() ) {
			$html .= '<p>' . esc_html( $order->get_billing_phone() ) . '</p>';
		}

		return $html . '</div>';
	}

	/**
	 * Bloque: número, fecha y vencimiento del presupuesto.
	 *
	 * @return string
	 */
	public static function render_quote_meta() {
		$quote = self::quote();

		if ( ! $quote ) {
			return self::placeholder( __( 'Datos del presupuesto', 'imagina-woo-quotes' ) );
		}

		$order = $quote->get_order();
		$rows  = array(
			__( 'Número', 'imagina-woo-quotes' ) => $order->get_order_number(),
			__( 'Fecha', 'imagina-woo-quotes' )  => wc_format_datetime( $order->get_date_created() ),
		);

		if ( $quote->get_expiry_date() ) {
			$rows[ __( 'Válido hasta', 'imagina-woo-quotes' ) ] = date_i18n( get_option( 'date_format' ), $quote->get_expiry_date() );
		}

		$html = '<table class="iwq-pdf-meta">';

		foreach ( $rows as $label => $value ) {
			$html .= sprintf( '<tr><th>%s</th><td>%s</td></tr>', esc_html( $label ), esc_html( $value ) );
		}

		return $html . '</table>';
	}

	/**
	 * Bloque: respuestas del formulario de solicitud.
	 *
	 * @return string
	 */
	public static function render_form_data() {
		$quote = self::quote();

		if ( ! $quote ) {
			return self::placeholder( __( 'Datos del formulario', 'imagina-woo-quotes' ) );
		}

		$data   = $quote->get_form_data();
		$fields = iwq_get_all_form_fields();
		$rows   = '';

		foreach ( $data as $id => $value ) {
			if ( ! isset( $fields[ $id ] ) || $fields[ $id ]['connect_to'] || 'heading' === $fields[ $id ]['type'] ) {
				continue;
			}

			if ( is_array( $value ) ? empty( $value ) : '' === (string) $value ) {
				continue;
			}

			$rows .= sprintf(
				'<tr><th>%s</th><td>%s</td></tr>',
				esc_html( $fields[ $id ]['label'] ),
				// Sin enlaces: en un PDF un adjunto se nombra, no se descarga.
				esc_html( iwq_format_form_value( $value, $fields[ $id ], 0, false ) )
			);
		}

		return $rows ? '<table class="iwq-pdf-form">' . $rows . '</table>' : '';
	}

	/**
	 * Bloque: enlaces para aceptar o rechazar.
	 *
	 * @return string
	 */
	public static function render_quote_actions() {
		$quote = self::quote();

		if ( ! $quote ) {
			return self::placeholder( __( 'Botones de respuesta', 'imagina-woo-quotes' ) );
		}

		if ( ! iwq_option_enabled( 'pdf_show_actions', true ) || ! $quote->is_actionable() ) {
			return '';
		}

		return sprintf(
			'<div class="iwq-pdf-actions"><a href="%1$s" class="iwq-pdf-button iwq-pdf-button--accept">%2$s</a> <a href="%3$s" class="iwq-pdf-button">%4$s</a></div>',
			esc_url( $quote->get_accept_url() ),
			esc_html__( 'Aceptar el presupuesto', 'imagina-woo-quotes' ),
			esc_url( $quote->get_reject_url() ),
			esc_html__( 'Rechazarlo', 'imagina-woo-quotes' )
		);
	}

	/**
	 * Bloque: logotipo de la tienda.
	 *
	 * @return string
	 */
	public static function render_store_logo() {
		$logo_id = (int) iwq_get_option( 'pdf_logo_id' );

		if ( ! $logo_id ) {
			$logo_id = (int) get_theme_mod( 'custom_logo' );
		}

		if ( ! $logo_id ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $logo_id, 'medium' );

		if ( ! $url ) {
			return '';
		}

		return sprintf(
			'<div class="iwq-pdf-logo"><img src="%s" alt="%s"></div>',
			esc_url( $url ),
			esc_attr( get_bloginfo( 'name' ) )
		);
	}
}
