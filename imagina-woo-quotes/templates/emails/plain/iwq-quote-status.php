<?php
/**
 * Email en texto plano: cambio de estado para el administrador.
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

printf(
	/* translators: 1: número de pedido, 2: estado legible. */
	esc_html__( 'El presupuesto %1$s ha pasado a: %2$s', 'imagina-woo-quotes' ) . "\n\n",
	esc_html( $order->get_order_number() ),
	esc_html( iwq_get_status_label( $order->get_status() ) )
);

echo esc_html__( 'Verlo:', 'imagina-woo-quotes' ) . " " . esc_url( $order->get_edit_order_url() ) . "\n";

echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, true, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, true, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, true, $email );

if ( $additional_content ) {
	echo "\n" . esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n";
}

echo "\n" . esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) . "\n";
