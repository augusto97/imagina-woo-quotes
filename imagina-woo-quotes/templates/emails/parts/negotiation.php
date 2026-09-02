<?php
/**
 * Última contraoferta y su mensaje.
 *
 * @package ImaginaWooQuotes
 *
 * @var WC_Order  $order Pedido.
 * @var IWQ_Quote $quote Presupuesto.
 */

defined( 'ABSPATH' ) || exit;

$iwq_offer = $quote->get_latest_counter_offer();

if ( ! $iwq_offer ) {
	return;
}

$iwq_args = array( 'currency' => $order->get_currency() );
?>
<table class="iwq-summary" cellpadding="0" cellspacing="0" role="presentation">
	<tr>
		<td class="iwq-label"><?php esc_html_e( 'Nuestro presupuesto', 'imagina-woo-quotes' ); ?></td>
		<td class="iwq-value"><?php echo esc_html( html_entity_decode( wp_strip_all_tags( wc_price( $order->get_total(), $iwq_args ) ), ENT_QUOTES, 'UTF-8' ) ); ?></td>
	</tr>
	<tr>
		<td class="iwq-label"><?php esc_html_e( 'Propuesta del cliente', 'imagina-woo-quotes' ); ?></td>
		<td class="iwq-value"><?php echo esc_html( html_entity_decode( wp_strip_all_tags( wc_price( $iwq_offer['offer'], $iwq_args ) ), ENT_QUOTES, 'UTF-8' ) ); ?></td>
	</tr>
</table>

<?php if ( ! empty( $iwq_offer['message'] ) ) : ?>
	<div class="iwq-quote-box">
		<?php echo esc_html( $iwq_offer['message'] ); ?>
		<div class="iwq-who"><?php echo esc_html( $order->get_formatted_billing_full_name() ); ?> · <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $iwq_offer['date'] ) ); ?></div>
	</div>
<?php endif; ?>
