<?php
/**
 * Datos del formulario dentro de un email HTML.
 *
 * @package ImaginaWooQuotes
 *
 * @var array    $form_data     Valores enviados por el cliente.
 * @var WC_Order $order         Pedido.
 * @var bool     $sent_to_admin Si el email va al administrador.
 */

defined( 'ABSPATH' ) || exit;

$iwq_fields = iwq_get_all_form_fields();
$iwq_rows   = array();

foreach ( $form_data as $iwq_id => $iwq_value ) {
	if ( ! isset( $iwq_fields[ $iwq_id ] ) ) {
		continue;
	}

	$iwq_field = $iwq_fields[ $iwq_id ];

	// Lo que ya se muestra en la ficha de facturación no se repite aquí.
	if ( $iwq_field['connect_to'] || 'heading' === $iwq_field['type'] ) {
		continue;
	}

	if ( is_array( $iwq_value ) ? empty( $iwq_value ) : '' === (string) $iwq_value ) {
		continue;
	}

	$iwq_rows[] = array( $iwq_field, $iwq_value );
}

if ( empty( $iwq_rows ) ) {
	return;
}
?>
<h2><?php esc_html_e( 'Datos de la solicitud', 'imagina-woo-quotes' ); ?></h2>

<table cellspacing="0" cellpadding="6" style="width:100%; border:1px solid #e5e7eb; margin-bottom:24px;" border="1">
	<tbody>
		<?php foreach ( $iwq_rows as $iwq_row ) : ?>
			<tr>
				<th scope="row" style="text-align:left; width:35%; background:#f9fafb;">
					<?php echo esc_html( $iwq_row[0]['label'] ); ?>
				</th>
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
