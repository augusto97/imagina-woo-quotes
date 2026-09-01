<?php
/**
 * Datos del formulario en un email de texto plano.
 *
 * @package ImaginaWooQuotes
 *
 * @var array    $form_data Valores enviados por el cliente.
 * @var WC_Order $order     Pedido.
 */

defined( 'ABSPATH' ) || exit;

$iwq_fields = iwq_get_all_form_fields();
$iwq_lines  = array();

foreach ( $form_data as $iwq_id => $iwq_value ) {
	if ( ! isset( $iwq_fields[ $iwq_id ] ) ) {
		continue;
	}

	$iwq_field = $iwq_fields[ $iwq_id ];

	if ( $iwq_field['connect_to'] || 'heading' === $iwq_field['type'] ) {
		continue;
	}

	if ( is_array( $iwq_value ) ? empty( $iwq_value ) : '' === (string) $iwq_value ) {
		continue;
	}

	$iwq_lines[] = $iwq_field['label'] . ': ' . iwq_format_form_value( $iwq_value, $iwq_field, $order->get_id(), false );
}

if ( empty( $iwq_lines ) ) {
	return;
}

echo "\n" . esc_html__( 'DATOS DE LA SOLICITUD', 'imagina-woo-quotes' ) . "\n";

foreach ( $iwq_lines as $iwq_line ) {
	echo esc_html( $iwq_line ) . "\n";
}
