<?php
/**
 * Validación y saneado del formulario de solicitud.
 *
 * Toda entrada del usuario pasa por aquí antes de tocar el pedido.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Form_Validator
 */
class IWQ_Form_Validator {

	/**
	 * Valores ya saneados, indexados por id de campo.
	 *
	 * @var array<string,mixed>
	 */
	private $values = array();

	/**
	 * Errores de validación, indexados por id de campo.
	 *
	 * @var array<string,string>
	 */
	private $errors = array();

	/**
	 * Valida la entrada contra la definición del formulario.
	 *
	 * @param array $input  Datos crudos de `$_POST['iwq_field']`.
	 * @param array $files  Datos crudos de `$_FILES`.
	 * @return bool True si no hay errores.
	 */
	public function validate( $input, $files = array() ) {
		$this->values = array();
		$this->errors = array();

		foreach ( iwq_get_form_fields() as $field ) {
			if ( 'heading' === $field['type'] ) {
				continue;
			}

			if ( 'file' === $field['type'] ) {
				$this->validate_file( $field, $files );
				continue;
			}

			$raw = isset( $input[ $field['id'] ] ) ? $input[ $field['id'] ] : '';

			$this->validate_field( $field, $raw );
		}

		/**
		 * Permite añadir validaciones propias al formulario.
		 *
		 * @param array               $errors Errores acumulados.
		 * @param array<string,mixed> $values Valores saneados.
		 */
		$this->errors = apply_filters( 'iwq_validate_form', $this->errors, $this->values );

		return empty( $this->errors );
	}

	/**
	 * Valida y sanea un campo que no es de archivo.
	 *
	 * @param array $field Definición del campo.
	 * @param mixed $raw   Valor crudo recibido.
	 * @return void
	 */
	private function validate_field( $field, $raw ) {
		$value    = $this->sanitize( $field, $raw );
		$required = 'yes' === $field['required'];
		$is_empty = is_array( $value ) ? empty( $value ) : ( '' === trim( (string) $value ) );

		if ( $required && $is_empty ) {
			$this->errors[ $field['id'] ] = sprintf(
				/* translators: %s: nombre del campo. */
				__( '«%s» es obligatorio.', 'imagina-woo-quotes' ),
				$field['label']
			);

			return;
		}

		if ( $is_empty ) {
			$this->values[ $field['id'] ] = $value;
			return;
		}

		$error = $this->check_format( $field, $value );

		if ( $error ) {
			$this->errors[ $field['id'] ] = $error;
			return;
		}

		$this->values[ $field['id'] ] = $value;
	}

	/**
	 * Sanea el valor según el tipo de campo.
	 *
	 * @param array $field Definición del campo.
	 * @param mixed $raw   Valor crudo.
	 * @return mixed
	 */
	private function sanitize( $field, $raw ) {
		$raw = wp_unslash( $raw );

		switch ( $field['type'] ) {
			case 'email':
				return sanitize_email( $raw );

			case 'textarea':
				return sanitize_textarea_field( $raw );

			case 'number':
				return is_numeric( $raw ) ? $raw + 0 : '';

			case 'acceptance':
				return $raw ? '1' : '';

			case 'multiselect':
			case 'checkbox':
				return array_values( array_map( 'sanitize_text_field', (array) $raw ) );

			case 'tel':
				// Conservamos solo lo que puede formar parte de un teléfono.
				return preg_replace( '/[^0-9\+\-\s\(\)\.]/', '', (string) $raw );

			default:
				return sanitize_text_field( $raw );
		}
	}

	/**
	 * Comprueba el formato del valor ya saneado.
	 *
	 * @param array $field Definición del campo.
	 * @param mixed $value Valor saneado.
	 * @return string Mensaje de error, o cadena vacía si es válido.
	 */
	private function check_format( $field, $value ) {
		switch ( $field['type'] ) {
			case 'email':
				if ( ! is_email( $value ) ) {
					return __( 'Introduce una dirección de email válida.', 'imagina-woo-quotes' );
				}
				break;

			case 'tel':
				// Cuenta solo dígitos: los formatos internacionales varían
				// demasiado como para validar con una expresión estricta.
				if ( strlen( preg_replace( '/\D/', '', $value ) ) < 6 ) {
					return __( 'Introduce un número de teléfono válido.', 'imagina-woo-quotes' );
				}
				break;

			case 'date':
				if ( ! $this->is_valid_date( $value ) ) {
					return __( 'Introduce una fecha válida.', 'imagina-woo-quotes' );
				}

				if ( $field['min_date'] && $value < $field['min_date'] ) {
					return sprintf(
						/* translators: %s: fecha mínima admitida. */
						__( 'La fecha no puede ser anterior al %s.', 'imagina-woo-quotes' ),
						$field['min_date']
					);
				}

				if ( $field['max_date'] && $value > $field['max_date'] ) {
					return sprintf(
						/* translators: %s: fecha máxima admitida. */
						__( 'La fecha no puede ser posterior al %s.', 'imagina-woo-quotes' ),
						$field['max_date']
					);
				}
				break;

			case 'select':
			case 'radio':
			case 'multiselect':
			case 'checkbox':
				if ( ! $this->values_are_allowed( $field, $value ) ) {
					return __( 'La opción elegida no es válida.', 'imagina-woo-quotes' );
				}
				break;
		}

		return '';
	}

	/**
	 * Comprueba que el valor está entre las opciones definidas.
	 *
	 * Impide que alguien envíe por POST una opción que no ofrecimos.
	 *
	 * @param array $field Definición del campo.
	 * @param mixed $value Valor saneado.
	 * @return bool
	 */
	private function values_are_allowed( $field, $value ) {
		// Las opciones de país y provincia las genera WooCommerce.
		if ( in_array( $field['type'], array( 'country', 'state' ), true ) ) {
			return true;
		}

		$allowed = array();

		foreach ( (array) $field['options'] as $option ) {
			$allowed[] = (string) ( is_array( $option ) ? $option['value'] : $option );
		}

		// Un campo de opciones sin opciones definidas no restringe nada.
		if ( empty( $allowed ) ) {
			return true;
		}

		foreach ( (array) $value as $single ) {
			if ( ! in_array( (string) $single, $allowed, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Comprueba que una cadena es una fecha ISA válida.
	 *
	 * @param string $value Valor en formato Y-m-d.
	 * @return bool
	 */
	private function is_valid_date( $value ) {
		$date = DateTime::createFromFormat( 'Y-m-d', $value );

		return $date && $date->format( 'Y-m-d' ) === $value;
	}

	/**
	 * Valida y guarda un archivo adjunto.
	 *
	 * @param array $field Definición del campo.
	 * @param array $files Contenido de `$_FILES`.
	 * @return void
	 */
	private function validate_file( $field, $files ) {
		$key      = 'iwq_file_' . $field['id'];
		$required = 'yes' === $field['required'];
		$has_file = isset( $files[ $key ] ) && UPLOAD_ERR_NO_FILE !== $files[ $key ]['error'];

		if ( ! $has_file ) {
			if ( $required ) {
				$this->errors[ $field['id'] ] = sprintf(
					/* translators: %s: nombre del campo. */
					__( 'Debes adjuntar un archivo en «%s».', 'imagina-woo-quotes' ),
					$field['label']
				);
			}

			return;
		}

		$result = IWQ_Uploads::handle( $files[ $key ], $field );

		if ( is_wp_error( $result ) ) {
			$this->errors[ $field['id'] ] = $result->get_error_message();
			return;
		}

		$this->values[ $field['id'] ] = $result;
	}

	/**
	 * Devuelve los valores saneados.
	 *
	 * @return array<string,mixed>
	 */
	public function get_values() {
		return $this->values;
	}

	/**
	 * Devuelve los errores de validación.
	 *
	 * @return array<string,string>
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Devuelve el primer error, útil para el aviso general.
	 *
	 * @return string
	 */
	public function get_first_error() {
		return $this->errors ? reset( $this->errors ) : '';
	}
}
