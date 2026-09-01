<?php
/**
 * Definiciones y utilidades del constructor de formularios.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tipos de campo disponibles en el constructor.
 *
 * @return array<string,array{label:string,icon:string,has_options:bool}>
 */
function iwq_get_form_field_types() {
	$types = array(
		'text'        => array(
			'label'       => __( 'Texto', 'imagina-woo-quotes' ),
			'icon'        => 'editor-textcolor',
			'has_options' => false,
		),
		'email'       => array(
			'label'       => __( 'Email', 'imagina-woo-quotes' ),
			'icon'        => 'email',
			'has_options' => false,
		),
		'tel'         => array(
			'label'       => __( 'Teléfono', 'imagina-woo-quotes' ),
			'icon'        => 'phone',
			'has_options' => false,
		),
		'number'      => array(
			'label'       => __( 'Número', 'imagina-woo-quotes' ),
			'icon'        => 'calculator',
			'has_options' => false,
		),
		'textarea'    => array(
			'label'       => __( 'Área de texto', 'imagina-woo-quotes' ),
			'icon'        => 'editor-alignleft',
			'has_options' => false,
		),
		'select'      => array(
			'label'       => __( 'Desplegable', 'imagina-woo-quotes' ),
			'icon'        => 'menu',
			'has_options' => true,
		),
		'multiselect' => array(
			'label'       => __( 'Selección múltiple', 'imagina-woo-quotes' ),
			'icon'        => 'list-view',
			'has_options' => true,
		),
		'radio'       => array(
			'label'       => __( 'Opción única', 'imagina-woo-quotes' ),
			'icon'        => 'marker',
			'has_options' => true,
		),
		'checkbox'    => array(
			'label'       => __( 'Casillas', 'imagina-woo-quotes' ),
			'icon'        => 'yes',
			'has_options' => true,
		),
		'country'     => array(
			'label'       => __( 'País', 'imagina-woo-quotes' ),
			'icon'        => 'admin-site',
			'has_options' => false,
		),
		'state'       => array(
			'label'       => __( 'Provincia / Estado', 'imagina-woo-quotes' ),
			'icon'        => 'location',
			'has_options' => false,
		),
		'date'        => array(
			'label'       => __( 'Fecha', 'imagina-woo-quotes' ),
			'icon'        => 'calendar-alt',
			'has_options' => false,
		),
		'time'        => array(
			'label'       => __( 'Hora', 'imagina-woo-quotes' ),
			'icon'        => 'clock',
			'has_options' => false,
		),
		'file'        => array(
			'label'       => __( 'Archivo adjunto', 'imagina-woo-quotes' ),
			'icon'        => 'paperclip',
			'has_options' => false,
		),
		'acceptance'  => array(
			'label'       => __( 'Aceptación', 'imagina-woo-quotes' ),
			'icon'        => 'privacy',
			'has_options' => false,
		),
		'heading'     => array(
			'label'       => __( 'Encabezado', 'imagina-woo-quotes' ),
			'icon'        => 'heading',
			'has_options' => false,
		),
	);

	/**
	 * Filtra los tipos de campo disponibles en el constructor.
	 *
	 * @param array $types Tipos registrados.
	 */
	return apply_filters( 'iwq_form_field_types', $types );
}

/**
 * Campos con los que arranca el formulario tras instalar el plugin.
 *
 * `connect_to` enlaza el campo con una propiedad del pedido, de modo que el
 * dato acaba en los campos nativos de WooCommerce en vez de quedar suelto
 * como metadato huérfano.
 *
 * @return array<string,array<string,mixed>>
 */
function iwq_get_default_form_fields() {
	$fields = array(
		'first_name' => array(
			'id'         => 'first_name',
			'type'       => 'text',
			'label'      => __( 'Nombre', 'imagina-woo-quotes' ),
			'required'   => 'yes',
			'enabled'    => 'yes',
			'width'      => 'half',
			'connect_to' => 'billing_first_name',
			'core'       => true,
		),
		'last_name'  => array(
			'id'         => 'last_name',
			'type'       => 'text',
			'label'      => __( 'Apellidos', 'imagina-woo-quotes' ),
			'required'   => 'yes',
			'enabled'    => 'yes',
			'width'      => 'half',
			'connect_to' => 'billing_last_name',
			'core'       => true,
		),
		'email'      => array(
			'id'         => 'email',
			'type'       => 'email',
			'label'      => __( 'Email', 'imagina-woo-quotes' ),
			'required'   => 'yes',
			'enabled'    => 'yes',
			'width'      => 'half',
			'connect_to' => 'billing_email',
			'core'       => true,
		),
		'phone'      => array(
			'id'         => 'phone',
			'type'       => 'tel',
			'label'      => __( 'Teléfono', 'imagina-woo-quotes' ),
			'required'   => 'no',
			'enabled'    => 'yes',
			'width'      => 'half',
			'connect_to' => 'billing_phone',
		),
		'company'    => array(
			'id'         => 'company',
			'type'       => 'text',
			'label'      => __( 'Empresa', 'imagina-woo-quotes' ),
			'required'   => 'no',
			'enabled'    => 'yes',
			'width'      => 'full',
			'connect_to' => 'billing_company',
		),
		'message'    => array(
			'id'       => 'message',
			'type'     => 'textarea',
			'label'    => __( 'Mensaje', 'imagina-woo-quotes' ),
			'required' => 'no',
			'enabled'  => 'yes',
			'width'    => 'full',
			'core'     => true,
		),
	);

	/**
	 * Filtra los campos por defecto del formulario.
	 *
	 * @param array $fields Campos por defecto.
	 */
	return apply_filters( 'iwq_default_form_fields', $fields );
}

/**
 * Devuelve los campos configurados, normalizados.
 *
 * @return array<string,array<string,mixed>>
 */
function iwq_get_form_fields() {
	$fields = iwq_get_option( 'form_fields', array() );

	if ( ! is_array( $fields ) || empty( $fields ) ) {
		$fields = iwq_get_default_form_fields();
	}

	$normalized = array();

	foreach ( $fields as $key => $field ) {
		$field = iwq_normalize_form_field( $field, $key );

		if ( 'yes' === $field['enabled'] ) {
			$normalized[ $field['id'] ] = $field;
		}
	}

	/**
	 * Filtra los campos que se van a renderizar en el formulario.
	 *
	 * @param array $normalized Campos normalizados y activos.
	 */
	return apply_filters( 'iwq_form_fields', $normalized );
}

/**
 * Rellena un campo con los valores por defecto de su estructura.
 *
 * @param array  $field Campo tal como está guardado.
 * @param string $key   Clave bajo la que estaba guardado.
 * @return array<string,mixed>
 */
function iwq_normalize_form_field( $field, $key = '' ) {
	$defaults = array(
		'id'          => $key,
		'type'        => 'text',
		'label'       => '',
		'placeholder' => '',
		'description' => '',
		'required'    => 'no',
		'enabled'     => 'yes',
		'width'       => 'full',
		'options'     => array(),
		'default'     => '',
		'connect_to'  => '',
		'core'        => false,
		'max_size'    => 0,
		'extensions'  => '',
		'min_date'    => '',
		'max_date'    => '',
	);

	$field = wp_parse_args( (array) $field, $defaults );

	if ( empty( $field['id'] ) ) {
		$field['id'] = $key ? $key : uniqid( 'field_' );
	}

	return $field;
}

/**
 * Propiedades del pedido a las que se puede enlazar un campo.
 *
 * @return array<string,string>
 */
function iwq_get_connectable_fields() {
	$fields = array(
		''                   => __( '— No enlazar —', 'imagina-woo-quotes' ),
		'billing_first_name' => __( 'Facturación: nombre', 'imagina-woo-quotes' ),
		'billing_last_name'  => __( 'Facturación: apellidos', 'imagina-woo-quotes' ),
		'billing_company'    => __( 'Facturación: empresa', 'imagina-woo-quotes' ),
		'billing_email'      => __( 'Facturación: email', 'imagina-woo-quotes' ),
		'billing_phone'      => __( 'Facturación: teléfono', 'imagina-woo-quotes' ),
		'billing_address_1'  => __( 'Facturación: dirección', 'imagina-woo-quotes' ),
		'billing_address_2'  => __( 'Facturación: dirección 2', 'imagina-woo-quotes' ),
		'billing_city'       => __( 'Facturación: ciudad', 'imagina-woo-quotes' ),
		'billing_state'      => __( 'Facturación: provincia', 'imagina-woo-quotes' ),
		'billing_postcode'   => __( 'Facturación: código postal', 'imagina-woo-quotes' ),
		'billing_country'    => __( 'Facturación: país', 'imagina-woo-quotes' ),
		'customer_note'      => __( 'Nota del pedido', 'imagina-woo-quotes' ),
	);

	/**
	 * Filtra las propiedades del pedido enlazables desde el formulario.
	 *
	 * @param array $fields Propiedades disponibles.
	 */
	return apply_filters( 'iwq_connectable_fields', $fields );
}

/**
 * Anchos de campo disponibles en la maquetación del formulario.
 *
 * @return array<string,string>
 */
function iwq_get_form_field_widths() {
	return array(
		'full'  => __( 'Ancho completo', 'imagina-woo-quotes' ),
		'half'  => __( 'Media fila', 'imagina-woo-quotes' ),
		'third' => __( 'Un tercio', 'imagina-woo-quotes' ),
	);
}
