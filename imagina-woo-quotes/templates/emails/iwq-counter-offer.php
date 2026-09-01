<?php
/**
 * Email: Contraoferta para el administrador.
 *
 * Se puede sobreescribir copiándolo a
 * `tu-tema/imagina-woo-quotes/emails/iwq-counter-offer.php`.
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

<?php $iwq_offer = $quote ? $quote->get_latest_counter_offer() : null; ?>

<p><?php
printf(
	/* translators: %s: nombre del cliente. */
	esc_html__( '%s ha propuesto un precio distinto para su presupuesto.', 'imagina-woo-quotes' ),
	esc_html( $order->get_formatted_billing_full_name() )
);
?></p>

<?php if ( $iwq_offer ) : ?>
	<table style="width:100%; margin:16px 0; border-collapse:collapse;">
		<tr>
			<th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;"><?php esc_html_e( 'Nuestro presupuesto', 'imagina-woo-quotes' ); ?></th>
			<td style="text-align:right;padding:8px;border-bottom:1px solid #e5e7eb;"><?php echo wp_kses_post( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></td>
		</tr>
		<tr>
			<th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;"><?php esc_html_e( 'Propuesta del cliente', 'imagina-woo-quotes' ); ?></th>
			<td style="text-align:right;padding:8px;border-bottom:1px solid #e5e7eb;font-weight:700;"><?php echo wp_kses_post( wc_price( $iwq_offer['offer'], array( 'currency' => $order->get_currency() ) ) ); ?></td>
		</tr>
	</table>

	<?php if ( ! empty( $iwq_offer['message'] ) ) : ?>
		<blockquote style="margin:16px 0;padding:12px 16px;background:#f9fafb;border-left:3px solid #2563eb;">
			<?php echo esc_html( $iwq_offer['message'] ); ?>
		</blockquote>
	<?php endif; ?>
<?php endif; ?>

<p>
	<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>" style="display:inline-block;padding:12px 24px;background:#2563eb;color:#ffffff;border-radius:6px;text-decoration:none;font-weight:600;">
		<?php esc_html_e( 'Responder a la contraoferta', 'imagina-woo-quotes' ); ?>
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
