<?php
/**
 * Respuestas del formulario.
 *
 * @package ImaginaWooQuotes
 *
 * @var WC_Order  $order         Pedido.
 * @var IWQ_Quote $quote         Presupuesto.
 * @var bool      $sent_to_admin Si el email va al administrador.
 */

defined( 'ABSPATH' ) || exit;

$iwq_fields = iwq_get_all_form_fields();
$iwq_rows   = array();

foreach ( $quote->get_form_data() as $iwq_id => $iwq_value ) {
	if ( ! isset( $iwq_fields[ $iwq_id ] ) || $iwq_fields[ $iwq_id ]['connect_to'] || 'heading' === $iwq_fields[ $iwq_id ]['type'] ) {
		continue;
	}

	if ( is_array( $iwq_value ) ? empty( $iwq_value ) : '' === (string) $iwq_value ) {
		continue;
	}

	$iwq_rows[] = array( $iwq_fields[ $iwq_id ], $iwq_value );
}

if ( empty( $iwq_rows ) ) {
	return;
}
?>
<h2 class="iwq-h2"><?php echo $sent_to_admin ? esc_html__( 'Lo que nos cuenta', 'imagina-woo-quotes' ) : esc_html__( 'Tus datos', 'imagina-woo-quotes' ); ?></h2>
<table class="iwq-form" cellpadding="0" cellspacing="0" role="presentation">
	<?php foreach ( $iwq_rows as $iwq_row ) : ?>
		<tr>
			<td class="iwq-label"><?php echo esc_html( $iwq_row[0]['label'] ); ?></td>
			<td>
				<?php
				// Los enlaces de descarga solo tienen sentido para quien puede usarlos.
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- iwq_format_form_value escapa su salida.
				echo iwq_format_form_value( $iwq_row[1], $iwq_row[0], $sent_to_admin ? $order->get_id() : 0, true );
				?>
			</td>
		</tr>
	<?php endforeach; ?>
</table>
