<?php
/**
 * Respuestas del formulario y adjuntos, en la pantalla del pedido.
 *
 * @package ImaginaWooQuotes
 *
 * @var WC_Order $order     Pedido.
 * @var array    $form_data Valores enviados por el cliente.
 * @var array    $fields    Definiciones de campo, incluidas las inactivas.
 */

defined( 'ABSPATH' ) || exit;

$iwq_rows = array();

foreach ( $form_data as $iwq_id => $iwq_value ) {
	if ( ! isset( $fields[ $iwq_id ] ) || 'heading' === $fields[ $iwq_id ]['type'] ) {
		continue;
	}

	if ( is_array( $iwq_value ) ? empty( $iwq_value ) : '' === (string) $iwq_value ) {
		continue;
	}

	$iwq_rows[] = array( $fields[ $iwq_id ], $iwq_value );
}

if ( empty( $iwq_rows ) ) {
	echo '<p class="iwq-muted">' . esc_html__( 'Esta solicitud no trae datos de formulario (por ejemplo, llegó desde el checkout).', 'imagina-woo-quotes' ) . '</p>';
	return;
}
?>
<table class="widefat striped iwq-request-data">
	<tbody>
		<?php foreach ( $iwq_rows as $iwq_row ) : ?>
			<tr>
				<th scope="row" style="width:30%"><?php echo esc_html( $iwq_row[0]['label'] ); ?></th>
				<td>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- iwq_format_form_value escapa su salida.
					echo iwq_format_form_value( $iwq_row[1], $iwq_row[0], $order->get_id(), true );
					?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
