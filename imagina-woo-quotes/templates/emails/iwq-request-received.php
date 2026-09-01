<?php
/**
 * Email: Confirmación de recepción para el cliente.
 *
 * Se puede sobreescribir copiándolo a
 * `tu-tema/imagina-woo-quotes/emails/iwq-request-received.php`.
 *
 * @package ImaginaWooQuotes
 *
 * @var WC_Order  $order              Pedido.
 * @var IWQ_Quote $quote              Presupuesto.
 * @var string    $email_heading      Encabezado.
 * @var string    $additional_content Contenido adicional configurado.
 * @var bool      $sent_to_admin      Si el email va al administrador.
 * @var WC_Email  $email              Email en curso.
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php
printf(
	/* translators: %s: nombre del cliente. */
	esc_html__( 'Hola %s:', 'imagina-woo-quotes' ),
	esc_html( $order->get_billing_first_name() )
);
?></p>

<p><?php esc_html_e( 'Hemos recibido tu solicitud de presupuesto y ya la estamos revisando. Te enviaremos los precios en cuanto la tengamos preparada.', 'imagina-woo-quotes' ); ?></p>

<p><?php esc_html_e( 'Estos son los productos que nos has pedido:', 'imagina-woo-quotes' ); ?></p>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, false, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, false, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, false, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
