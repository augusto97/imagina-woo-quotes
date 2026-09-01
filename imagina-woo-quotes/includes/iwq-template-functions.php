<?php
/**
 * Funciones de presentación compartidas por plantillas, emails y PDF.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Convierte el valor de un campo del formulario en texto legible.
 *
 * Los adjuntos se devuelven como enlace de descarga si quien mira tiene
 * permiso, y como nombre de archivo en caso contrario.
 *
 * @param mixed  $value    Valor guardado.
 * @param array  $field    Definición del campo.
 * @param int    $order_id ID del pedido, necesario para los adjuntos.
 * @param bool   $as_html  Si true devuelve HTML; si no, texto plano.
 * @return string
 */
function iwq_format_form_value( $value, $field, $order_id = 0, $as_html = true ) {
	if ( is_array( $value ) && isset( $value['file'], $value['name'] ) ) {
		if ( ! $as_html || ! $order_id ) {
			return $value['name'];
		}

		return sprintf(
			'<a href="%s">%s</a> <span class="iwq-filesize">(%s)</span>',
			esc_url( IWQ_Uploads::get_download_url( $order_id, $field['id'] ) ),
			esc_html( $value['name'] ),
			esc_html( size_format( $value['size'] ) )
		);
	}

	if ( is_array( $value ) ) {
		$labels = array();

		foreach ( $value as $single ) {
			$labels[] = iwq_get_option_label( $field, $single );
		}

		$text = implode( ', ', $labels );

		return $as_html ? esc_html( $text ) : $text;
	}

	if ( 'acceptance' === $field['type'] ) {
		return $value
			? __( 'Sí', 'imagina-woo-quotes' )
			: __( 'No', 'imagina-woo-quotes' );
	}

	if ( 'country' === $field['type'] ) {
		$countries = WC()->countries->get_countries();
		$value     = isset( $countries[ $value ] ) ? $countries[ $value ] : $value;
	}

	if ( 'date' === $field['type'] && $value ) {
		$timestamp = strtotime( $value );
		$value     = $timestamp ? date_i18n( get_option( 'date_format' ), $timestamp ) : $value;
	}

	$value = iwq_get_option_label( $field, $value );

	return $as_html ? nl2br( esc_html( $value ) ) : $value;
}

/**
 * Devuelve la etiqueta de una opción a partir de su valor guardado.
 *
 * Sin esto, en el email se vería el valor interno («opt_2») en lugar del
 * texto que el cliente eligió.
 *
 * @param array  $field Definición del campo.
 * @param string $value Valor guardado.
 * @return string
 */
function iwq_get_option_label( $field, $value ) {
	if ( empty( $field['options'] ) || ! is_array( $field['options'] ) ) {
		return (string) $value;
	}

	foreach ( $field['options'] as $option ) {
		if ( is_array( $option ) && isset( $option['value'] ) && (string) $option['value'] === (string) $value ) {
			return $option['label'];
		}
	}

	return (string) $value;
}

/**
 * Devuelve las definiciones de campo indexadas por id, incluidas las de
 * campos que ya no están activos.
 *
 * Un presupuesto de hace seis meses puede contener campos que después se
 * quitaron del formulario: seguimos necesitando su etiqueta para mostrarlo.
 *
 * @return array<string,array>
 */
function iwq_get_all_form_fields() {
	$saved = iwq_get_option( 'form_fields', array() );
	$saved = is_array( $saved ) && $saved ? $saved : iwq_get_default_form_fields();

	$fields = array();

	foreach ( $saved as $key => $field ) {
		$field                  = iwq_normalize_form_field( $field, $key );
		$fields[ $field['id'] ] = $field;
	}

	return $fields;
}

/**
 * Devuelve el color asociado a un estado de presupuesto.
 *
 * @param string $status Estado sin prefijo.
 * @return string Color hexadecimal.
 */
function iwq_get_status_color( $status ) {
	$colors = array(
		'iwq-new'      => '#3730a3',
		'iwq-pending'  => '#92400e',
		'iwq-accepted' => '#065f46',
		'iwq-rejected' => '#991b1b',
		'iwq-expired'  => '#4b5563',
	);

	return isset( $colors[ $status ] ) ? $colors[ $status ] : '#4b5563';
}
