<?php
/**
 * Resumen del presupuesto: número, fecha, validez, estado, total.
 *
 * @package ImaginaWooQuotes
 *
 * @var WC_Order  $order Pedido.
 * @var IWQ_Quote $quote Presupuesto.
 */

defined( 'ABSPATH' ) || exit;

$iwq_rows = array(
	__( 'Presupuesto', 'imagina-woo-quotes' ) => '#' . $order->get_order_number(),
	__( 'Fecha', 'imagina-woo-quotes' )       => wc_format_datetime( $order->get_date_created() ),
);

if ( $quote->get_expiry_date() ) {
	$iwq_rows[ __( 'Válido hasta', 'imagina-woo-quotes' ) ] = date_i18n( get_option( 'date_format' ), $quote->get_expiry_date() );
}

$iwq_rows[ __( 'Estado', 'imagina-woo-quotes' ) ] = iwq_get_status_label( $order->get_status() );

if ( $quote->prices_visible() ) {
	$iwq_rows[ $quote->is_estimate() ? __( 'Importe orientativo', 'imagina-woo-quotes' ) : __( 'Total', 'imagina-woo-quotes' ) ] = wp_strip_all_tags( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) );
}
?>
<table class="iwq-summary" cellpadding="0" cellspacing="0" role="presentation">
	<?php foreach ( $iwq_rows as $iwq_label => $iwq_value ) : ?>
		<tr>
			<td class="iwq-label"><?php echo esc_html( $iwq_label ); ?></td>
			<td class="iwq-value"><?php echo esc_html( html_entity_decode( $iwq_value, ENT_QUOTES, 'UTF-8' ) ); ?></td>
		</tr>
	<?php endforeach; ?>
</table>
