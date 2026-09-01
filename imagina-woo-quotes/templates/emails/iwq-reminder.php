<?php
/**
 * Email: Recordatorio de vencimiento para el cliente.
 *
 * Se puede sobreescribir copiándolo a
 * `tu-tema/imagina-woo-quotes/emails/iwq-reminder.php`.
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

<?php if ( $quote && $quote->get_expiry_date() ) : ?>
	<p><?php
	printf(
		/* translators: %s: fecha de vencimiento. */
		esc_html__( 'Te escribimos para recordarte que tu presupuesto vence el %s. Si sigue interesándote, puedes aceptarlo desde aquí.', 'imagina-woo-quotes' ),
		esc_html( date_i18n( get_option( 'date_format' ), $quote->get_expiry_date() ) )
	);
	?></p>
<?php endif; ?>

<?php if ( $quote && $quote->is_actionable() ) : ?>
	<p style="margin:24px 0;">
		<a href="<?php echo esc_url( $quote->get_accept_url() ); ?>" style="display:inline-block;padding:12px 24px;background:#059669;color:#ffffff;border-radius:6px;text-decoration:none;font-weight:600;">
			<?php esc_html_e( 'Aceptar el presupuesto', 'imagina-woo-quotes' ); ?>
		</a>
	</p>
<?php endif; ?>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, false, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, false, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, false, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
