<?php
/**
 * Email en texto plano: recordatorio para el cliente.
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
	/* translators: %s: nombre del cliente. */
	esc_html__( 'Hola %s:', 'imagina-woo-quotes' ) . "\n\n",
	esc_html( $order->get_billing_first_name() )
);

if ( $quote && $quote->get_expiry_date() ) {
	printf(
		/* translators: %s: fecha de vencimiento. */
		esc_html__( 'Tu presupuesto vence el %s.', 'imagina-woo-quotes' ) . "\n\n",
		esc_html( date_i18n( get_option( 'date_format' ), $quote->get_expiry_date() ) )
	);
}

if ( $quote && $quote->is_actionable() ) {
	echo esc_html__( 'Aceptarlo:', 'imagina-woo-quotes' ) . " " . esc_url( $quote->get_accept_url() ) . "\n";
}

echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, true, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, true, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, true, $email );

if ( $additional_content ) {
	echo "\n" . esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n";
}

echo "\n" . esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) . "\n";
