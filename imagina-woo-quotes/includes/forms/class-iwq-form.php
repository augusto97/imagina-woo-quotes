<?php
/**
 * Render del formulario de solicitud de presupuesto.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Form
 */
class IWQ_Form {

	/**
	 * Pinta el formulario completo.
	 *
	 * @param array $values Valores previos, para repintar tras un error.
	 * @param array $errors Errores por campo.
	 * @return string HTML del formulario.
	 */
	public static function render( $values = array(), $errors = array() ) {
		$fields = iwq_get_form_fields();

		if ( empty( $fields ) ) {
			return '';
		}

		$values = self::merge_with_customer_defaults( $values );

		$html = '<div class="iwq-form-grid">';

		foreach ( $fields as $field ) {
			$value = isset( $values[ $field['id'] ] ) ? $values[ $field['id'] ] : $field['default'];
			$error = isset( $errors[ $field['id'] ] ) ? $errors[ $field['id'] ] : '';

			$html .= self::render_field( $field, $value, $error );
		}

		return $html . '</div>';
	}

	/**
	 * Precarga los datos que ya conocemos del cliente registrado.
	 *
	 * @param array $values Valores actuales.
	 * @return array
	 */
	private static function merge_with_customer_defaults( $values ) {
		if ( ! is_user_logged_in() || ! iwq_option_enabled( 'autocomplete_form', true ) ) {
			return $values;
		}

		$customer = new WC_Customer( get_current_user_id() );

		foreach ( iwq_get_form_fields() as $field ) {
			if ( ( isset( $values[ $field['id'] ] ) && '' !== $values[ $field['id'] ] ) || empty( $field['connect_to'] ) ) {
				continue;
			}

			$getter = 'get_' . $field['connect_to'];

			if ( is_callable( array( $customer, $getter ) ) ) {
				$values[ $field['id'] ] = $customer->$getter();
			}
		}

		return $values;
	}

	/**
	 * Pinta un campo individual con su etiqueta, descripción y error.
	 *
	 * @param array  $field Definición del campo.
	 * @param mixed  $value Valor actual.
	 * @param string $error Mensaje de error, si lo hay.
	 * @return string
	 */
	public static function render_field( $field, $value = '', $error = '' ) {
		$name     = 'iwq_field[' . $field['id'] . ']';
		$id       = 'iwq-field-' . $field['id'];
		$required = 'yes' === $field['required'];

		$classes = array( 'iwq-field', 'iwq-field--' . $field['type'], 'iwq-field--' . $field['width'] );

		if ( $error ) {
			$classes[] = 'iwq-field--error';
		}

		$html = sprintf( '<div class="%s">', esc_attr( implode( ' ', $classes ) ) );

		// El encabezado es puramente visual: no tiene control ni valor.
		if ( 'heading' === $field['type'] ) {
			$html .= sprintf( '<h3 class="iwq-field__heading">%s</h3>', esc_html( $field['label'] ) );

			if ( $field['description'] ) {
				$html .= sprintf( '<p class="iwq-field__description">%s</p>', esc_html( $field['description'] ) );
			}

			return $html . '</div>';
		}

		// La aceptación lleva su etiqueta a la derecha de la casilla.
		if ( 'acceptance' !== $field['type'] ) {
			$html .= sprintf(
				'<label class="iwq-field__label" for="%1$s">%2$s%3$s</label>',
				esc_attr( $id ),
				esc_html( $field['label'] ),
				$required ? '<abbr class="iwq-field__required" title="' . esc_attr__( 'Obligatorio', 'imagina-woo-quotes' ) . '">*</abbr>' : ''
			);
		}

		$html .= self::render_control( $field, $value, $name, $id );

		if ( $field['description'] && 'acceptance' !== $field['type'] ) {
			$html .= sprintf(
				'<span class="iwq-field__description" id="%1$s-desc">%2$s</span>',
				esc_attr( $id ),
				esc_html( $field['description'] )
			);
		}

		if ( $error ) {
			$html .= sprintf(
				'<span class="iwq-field__error" role="alert">%s</span>',
				esc_html( $error )
			);
		}

		return $html . '</div>';
	}

	/**
	 * Pinta el control del campo según su tipo.
	 *
	 * @param array  $field Definición del campo.
	 * @param mixed  $value Valor actual.
	 * @param string $name  Atributo name.
	 * @param string $id    Atributo id.
	 * @return string
	 */
	private static function render_control( $field, $value, $name, $id ) {
		$required = 'yes' === $field['required'] ? ' required aria-required="true"' : '';
		$describe = $field['description'] ? sprintf( ' aria-describedby="%s-desc"', esc_attr( $id ) ) : '';

		switch ( $field['type'] ) {
			case 'textarea':
				return sprintf(
					'<textarea class="iwq-field__control" name="%1$s" id="%2$s" rows="5" placeholder="%3$s"%4$s%5$s>%6$s</textarea>',
					esc_attr( $name ),
					esc_attr( $id ),
					esc_attr( $field['placeholder'] ),
					$required,
					$describe,
					esc_textarea( $value )
				);

			case 'select':
			case 'country':
			case 'state':
				return self::render_select( $field, $value, $name, $id, $required, $describe );

			case 'multiselect':
				return self::render_select( $field, $value, $name . '[]', $id, $required, $describe, true );

			case 'radio':
			case 'checkbox':
				return self::render_choices( $field, $value, $name, $id );

			case 'acceptance':
				return sprintf(
					'<label class="iwq-field__acceptance" for="%1$s"><input type="checkbox" name="%2$s" id="%1$s" value="1"%3$s%4$s> <span>%5$s</span></label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( $value, '1', false ),
					$required,
					wp_kses_post( $field['label'] )
				);

			case 'file':
				return self::render_file( $field, $id, $required );

			case 'date':
			case 'time':
			case 'number':
			case 'email':
			case 'tel':
				return self::render_input( $field['type'], $field, $value, $name, $id, $required, $describe );

			default:
				return self::render_input( 'text', $field, $value, $name, $id, $required, $describe );
		}
	}

	/**
	 * Pinta un input simple.
	 *
	 * @param string $type     Tipo HTML del input.
	 * @param array  $field    Definición del campo.
	 * @param mixed  $value    Valor actual.
	 * @param string $name     Atributo name.
	 * @param string $id       Atributo id.
	 * @param string $required Fragmento de atributos de obligatoriedad.
	 * @param string $describe Fragmento aria-describedby.
	 * @return string
	 */
	private static function render_input( $type, $field, $value, $name, $id, $required, $describe ) {
		$extra = '';

		if ( 'date' === $type ) {
			$extra .= $field['min_date'] ? sprintf( ' min="%s"', esc_attr( $field['min_date'] ) ) : '';
			$extra .= $field['max_date'] ? sprintf( ' max="%s"', esc_attr( $field['max_date'] ) ) : '';
		}

		return sprintf(
			'<input type="%1$s" class="iwq-field__control" name="%2$s" id="%3$s" value="%4$s" placeholder="%5$s"%6$s%7$s%8$s%9$s>',
			esc_attr( $type ),
			esc_attr( $name ),
			esc_attr( $id ),
			esc_attr( $value ),
			esc_attr( $field['placeholder'] ),
			$required,
			$describe,
			$extra,
			self::get_autocomplete_attribute( $field )
		);
	}

	/**
	 * Deduce el valor de `autocomplete` a partir del enlace del campo.
	 *
	 * Que el navegador rellene solo la dirección es una de las mejoras de
	 * usabilidad más baratas que existen.
	 *
	 * @param array $field Definición del campo.
	 * @return string
	 */
	private static function get_autocomplete_attribute( $field ) {
		$map = array(
			'billing_first_name' => 'given-name',
			'billing_last_name'  => 'family-name',
			'billing_company'    => 'organization',
			'billing_email'      => 'email',
			'billing_phone'      => 'tel',
			'billing_address_1'  => 'address-line1',
			'billing_address_2'  => 'address-line2',
			'billing_city'       => 'address-level2',
			'billing_state'      => 'address-level1',
			'billing_postcode'   => 'postal-code',
			'billing_country'    => 'country',
		);

		return isset( $map[ $field['connect_to'] ] )
			? sprintf( ' autocomplete="%s"', esc_attr( $map[ $field['connect_to'] ] ) )
			: '';
	}

	/**
	 * Pinta un desplegable, incluidos los de país y provincia.
	 *
	 * @param array  $field    Definición del campo.
	 * @param mixed  $value    Valor actual.
	 * @param string $name     Atributo name.
	 * @param string $id       Atributo id.
	 * @param string $required Fragmento de obligatoriedad.
	 * @param string $describe Fragmento aria-describedby.
	 * @param bool   $multiple Si admite varios valores.
	 * @return string
	 */
	private static function render_select( $field, $value, $name, $id, $required, $describe, $multiple = false ) {
		$options = self::get_field_options( $field );
		$value   = array_map( 'strval', (array) $value );

		$html = sprintf(
			'<select class="iwq-field__control" name="%1$s" id="%2$s"%3$s%4$s%5$s>',
			esc_attr( $name ),
			esc_attr( $id ),
			$multiple ? ' multiple' : '',
			$required,
			$describe
		);

		if ( ! $multiple ) {
			$html .= sprintf(
				'<option value="">%s</option>',
				esc_html( $field['placeholder'] ? $field['placeholder'] : __( 'Elige una opción', 'imagina-woo-quotes' ) )
			);
		}

		foreach ( $options as $key => $label ) {
			$html .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $key ),
				in_array( (string) $key, $value, true ) ? ' selected' : '',
				esc_html( $label )
			);
		}

		return $html . '</select>';
	}

	/**
	 * Pinta un grupo de radios o casillas.
	 *
	 * @param array  $field Definición del campo.
	 * @param mixed  $value Valor actual.
	 * @param string $name  Atributo name.
	 * @param string $id    Atributo id.
	 * @return string
	 */
	private static function render_choices( $field, $value, $name, $id ) {
		$is_checkbox = 'checkbox' === $field['type'];
		$options     = self::get_field_options( $field );
		$value       = array_map( 'strval', (array) $value );
		$input_name  = $is_checkbox ? $name . '[]' : $name;

		$html  = '<div class="iwq-field__choices" role="group">';
		$index = 0;

		foreach ( $options as $key => $label ) {
			$choice_id = $id . '-' . $index;

			$html .= sprintf(
				'<label class="iwq-field__choice" for="%1$s"><input type="%2$s" name="%3$s" id="%1$s" value="%4$s"%5$s> <span>%6$s</span></label>',
				esc_attr( $choice_id ),
				$is_checkbox ? 'checkbox' : 'radio',
				esc_attr( $input_name ),
				esc_attr( $key ),
				in_array( (string) $key, $value, true ) ? ' checked' : '',
				esc_html( $label )
			);

			++$index;
		}

		return $html . '</div>';
	}

	/**
	 * Pinta un campo de subida de archivo.
	 *
	 * @param array  $field    Definición del campo.
	 * @param string $id       Atributo id.
	 * @param string $required Fragmento de obligatoriedad.
	 * @return string
	 */
	private static function render_file( $field, $id, $required ) {
		$accept   = IWQ_Uploads::get_accept_attribute( $field );
		$max_size = IWQ_Uploads::get_max_size( $field );

		return sprintf(
			'<input type="file" class="iwq-field__control iwq-field__file" name="iwq_file_%1$s" id="%2$s"%3$s%4$s><span class="iwq-field__hint">%5$s</span>',
			esc_attr( $field['id'] ),
			esc_attr( $id ),
			$accept ? sprintf( ' accept="%s"', esc_attr( $accept ) ) : '',
			$required,
			esc_html(
				sprintf(
					/* translators: %s: tamaño máximo legible, por ejemplo «5 MB». */
					__( 'Tamaño máximo: %s', 'imagina-woo-quotes' ),
					size_format( $max_size )
				)
			)
		);
	}

	/**
	 * Devuelve las opciones de un campo de selección.
	 *
	 * Los campos de país y provincia toman sus opciones de WooCommerce, así
	 * que respetan los países a los que la tienda realmente vende.
	 *
	 * @param array $field Definición del campo.
	 * @return array<string,string>
	 */
	private static function get_field_options( $field ) {
		if ( 'country' === $field['type'] ) {
			return WC()->countries->get_allowed_countries();
		}

		if ( 'state' === $field['type'] ) {
			$states = WC()->countries->get_states( WC()->countries->get_base_country() );

			return is_array( $states ) ? $states : array();
		}

		$options = array();

		foreach ( (array) $field['options'] as $option ) {
			if ( is_array( $option ) ) {
				$options[ $option['value'] ] = $option['label'];
			} else {
				$options[ $option ] = $option;
			}
		}

		return $options;
	}
}
