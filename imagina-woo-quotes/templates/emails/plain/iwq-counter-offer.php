<?php
/**
 * Email en texto plano: contraoferta para el administrador.
 *
 * @package ImaginaWooQuotes
 *
 * @var WC_Order  $order              Pedido.
 * @var IWQ_Quote $quote              Presupuesto.
 * @var string    $email_heading      Encabezado.
 * @var string    $additional_content Contenido adicional.
 * @var bool      $sent_to_admin      Si el email va al administrador.
 * @var WC_Email  $email              Email en curso.
 */

defined( 'ABSPATH' ) || exit;

echo "= " . esc_html( $email_heading ) . " =\n\n";

$iwq_offer = $quote ? $quote->get_latest_counter_offer() : null;

printf(
	/* translators: %s: nombre del cliente. */
	esc_html__( '%s ha propuesto un precio distinto.', 'imagina-woo-quotes' ) . "\n\n",
	esc_html( $order->get_formatted_billing_full_name() )
);

if ( $iwq_offer ) {
	echo esc_html__( 'Nuestro presupuesto:', 'imagina-woo-quotes' ) . " " . esc_html( wp_strip_all_tags( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ) ) . "\n";
	echo esc_html__( 'Propuesta del cliente:', 'imagina-woo-quotes' ) . " " . esc_html( wp_strip_all_tags( wc_price( $iwq_offer['offer'], array( 'currency' => $order->get_currency() ) ) ) ) . "\n";

	if ( ! empty( $iwq_offer['message'] ) ) {
		echo "\n" . esc_html( $iwq_offer['message'] ) . "\n";
	}
}

echo "\n" . esc_html__( 'Responder:', 'imagina-woo-quotes' ) . " " . esc_url( $order->get_edit_order_url() ) . "\n";

echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, true, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, true, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, true, $email );

if ( $additional_content ) {
	echo "\n" . esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n";
}

echo "\n" . esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) . "\n";
