<?php
/**
 * Marcadores de texto de las plantillas de PDF.
 *
 * Permiten escribir «Presupuesto {order_number}» en un párrafo del editor y
 * que salga el número real al generar el documento.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_PDF_Placeholders
 */
class IWQ_PDF_Placeholders {

	/**
	 * Devuelve los marcadores disponibles con su descripción.
	 *
	 * Se usa también para la ayuda del editor, así la lista nunca se
	 * desincroniza de lo que realmente se reemplaza.
	 *
	 * @return array<string,string>
	 */
	public static function get_definitions() {
		return array(
			'{order_number}'   => __( 'Número del presupuesto', 'imagina-woo-quotes' ),
			'{order_date}'     => __( 'Fecha de la solicitud', 'imagina-woo-quotes' ),
			'{expiry_date}'    => __( 'Fecha de vencimiento', 'imagina-woo-quotes' ),
			'{customer_name}'  => __( 'Nombre completo del cliente', 'imagina-woo-quotes' ),
			'{customer_email}' => __( 'Email del cliente', 'imagina-woo-quotes' ),
			'{customer_phone}' => __( 'Teléfono del cliente', 'imagina-woo-quotes' ),
			'{company}'        => __( 'Empresa del cliente', 'imagina-woo-quotes' ),
			'{quote_total}'    => __( 'Importe total', 'imagina-woo-quotes' ),
			'{quote_status}'   => __( 'Estado del presupuesto', 'imagina-woo-quotes' ),
			'{accept_url}'     => __( 'Enlace para aceptar', 'imagina-woo-quotes' ),
			'{reject_url}'     => __( 'Enlace para rechazar', 'imagina-woo-quotes' ),
			'{site_title}'     => __( 'Nombre de la tienda', 'imagina-woo-quotes' ),
			'{site_url}'       => __( 'Dirección de la tienda', 'imagina-woo-quotes' ),
		);
	}

	/**
	 * Reemplaza los marcadores por sus valores reales.
	 *
	 * @param string   $content Contenido con marcadores.
	 * @param WC_Order $order   Pedido.
	 * @return string
	 */
	public static function replace( $content, $order ) {
		$quote = iwq_get_quote( $order );

		$values = array(
			'{order_number}'   => $order->get_order_number(),
			'{order_date}'     => wc_format_datetime( $order->get_date_created() ),
			'{expiry_date}'    => $quote && $quote->get_expiry_date()
				? date_i18n( get_option( 'date_format' ), $quote->get_expiry_date() )
				: '—',
			'{customer_name}'  => $order->get_formatted_billing_full_name(),
			'{customer_email}' => $order->get_billing_email(),
			'{customer_phone}' => $order->get_billing_phone(),
			'{company}'        => $order->get_billing_company(),
			'{quote_total}'    => wp_strip_all_tags( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ),
			'{quote_status}'   => iwq_get_status_label( $order->get_status() ),
			'{accept_url}'     => $quote ? $quote->get_accept_url() : '',
			'{reject_url}'     => $quote ? $quote->get_reject_url() : '',
			'{site_title}'     => get_bloginfo( 'name' ),
			'{site_url}'       => home_url( '/' ),
		);

		/**
		 * Filtra los valores de los marcadores del PDF.
		 *
		 * @param array    $values Marcador a valor.
		 * @param WC_Order $order  Pedido.
		 */
		$values = apply_filters( 'iwq_pdf_placeholder_values', $values, $order );

		return strtr( $content, array_map( 'wp_kses_post', $values ) );
	}
}
