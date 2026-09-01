<?php
/**
 * Email: Nueva solicitud para el administrador.
 *
 * Se puede sobreescribir copiándolo a
 * `tu-tema/imagina-woo-quotes/emails/iwq-new-request.php`.
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
	esc_html__( '%s ha solicitado un presupuesto. Estos son los productos que le interesan:', 'imagina-woo-quotes' ),
	esc_html( $order->get_formatted_billing_full_name() )
);
?></p>

<p>
	<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>" style="display:inline-block;padding:12px 24px;background:#2563eb;color:#ffffff;border-radius:6px;text-decoration:none;font-weight:600;">
		<?php esc_html_e( 'Preparar el presupuesto', 'imagina-woo-quotes' ); ?>
	</a>
</p>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, false, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, false, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, false, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
