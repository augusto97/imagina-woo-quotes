<?php
/**
 * Render de los campos de la página de ajustes.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Settings_Fields
 */
class IWQ_Settings_Fields {

	/**
	 * Pinta un campo completo con su etiqueta y descripción.
	 *
	 * @param string $key   Clave sin prefijo.
	 * @param array  $field Definición del campo.
	 * @return void
	 */
	public static function render( $key, $field ) {
		$name  = 'iwq_' . $key;
		$value = get_option( $name, '' );

		if ( 'form_builder' === $field['type'] ) {
			echo '<div class="iwq-field-row iwq-field-row--wide">';
			self::render_form_builder( $name );
			echo '</div>';

			return;
		}

		printf(
			'<div class="iwq-field-row iwq-field-row--%3$s"><div class="iwq-field-row__label"><label for="%1$s">%2$s</label></div><div class="iwq-field-row__control">',
			esc_attr( $name ),
			esc_html( $field['label'] ),
			esc_attr( $field['type'] )
		);

		self::render_control( $name, $field, $value );

		if ( ! empty( $field['desc'] ) ) {
			printf( '<p class="iwq-field-row__desc">%s</p>', esc_html( $field['desc'] ) );
		}

		echo '</div></div>';
	}

	/**
	 * Pinta el control según su tipo.
	 *
	 * @param string $name  Atributo name.
	 * @param array  $field Definición.
	 * @param mixed  $value Valor guardado.
	 * @return void
	 */
	private static function render_control( $name, $field, $value ) {
		switch ( $field['type'] ) {
			case 'checkbox':
				printf(
					'<label class="iwq-switch"><input type="hidden" name="%1$s" value="no"><input type="checkbox" name="%1$s" id="%1$s" value="yes"%2$s><span class="iwq-switch__track"></span></label>',
					esc_attr( $name ),
					checked( $value, 'yes', false )
				);
				break;

			case 'textarea':
				printf(
					'<textarea name="%1$s" id="%1$s" rows="4" class="large-text code">%2$s</textarea>',
					esc_attr( $name ),
					esc_textarea( $value )
				);
				break;

			case 'number':
				printf(
					'<input type="number" name="%1$s" id="%1$s" value="%2$s" class="small-text" min="0" step="1">',
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;

			case 'password':
				printf(
					'<input type="password" name="%1$s" id="%1$s" value="%2$s" class="regular-text" autocomplete="off">',
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;

			case 'select':
				self::render_select( $name, $field['options'], $value );
				break;

			case 'page':
				wp_dropdown_pages(
					array(
						'name'              => $name,
						'id'                => $name,
						'selected'          => (int) $value,
						'show_option_none'  => __( '— Ninguna —', 'imagina-woo-quotes' ),
						'option_none_value' => 0,
					)
				);
				break;

			case 'pdf_template':
				self::render_select(
					$name,
					array( 0 => __( '— Diseño por defecto —', 'imagina-woo-quotes' ) ) + IWQ_PDF_Template_CPT::get_choices(),
					$value
				);

				printf(
					' <a class="iwq-btn iwq-btn--secondary iwq-btn--sm" href="%s">%s</a>',
					esc_url( admin_url( 'edit.php?post_type=' . IWQ_PDF_Template_CPT::POST_TYPE ) ),
					esc_html__( 'Gestionar plantillas', 'imagina-woo-quotes' )
				);
				break;

			case 'roles':
				self::render_roles( $name, $value );
				break;

			case 'terms':
				self::render_terms( $name, $field['taxonomy'], $value );
				break;

			case 'products':
				self::render_products( $name, $value );
				break;

			case 'media':
				self::render_media( $name, $value );
				break;

			case 'color':
				printf(
					'<input type="text" name="%1$s" id="%1$s" value="%2$s" class="iwq-color-field" data-default-color="#2563eb">',
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;

			case 'email_style':
				self::render_email_styles( $name, $value );
				break;

			default:
				printf(
					'<input type="text" name="%1$s" id="%1$s" value="%2$s" class="regular-text">',
					esc_attr( $name ),
					esc_attr( $value )
				);
		}
	}

	/**
	 * Pinta un desplegable.
	 *
	 * @param string $name    Atributo name.
	 * @param array  $options Opciones.
	 * @param mixed  $value   Valor actual.
	 * @return void
	 */
	private static function render_select( $name, $options, $value ) {
		printf( '<select name="%1$s" id="%1$s">', esc_attr( $name ) );

		foreach ( $options as $key => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $key ),
				selected( (string) $value, (string) $key, false ),
				esc_html( $label )
			);
		}

		echo '</select>';
	}

	/**
	 * Pinta el selector de roles.
	 *
	 * @param string $name  Atributo name.
	 * @param mixed  $value Roles seleccionados.
	 * @return void
	 */
	private static function render_roles( $name, $value ) {
		$value = is_array( $value ) ? $value : array();
		$roles = array( 'guest' => __( 'Visitantes sin cuenta', 'imagina-woo-quotes' ) );

		foreach ( wp_roles()->get_names() as $key => $label ) {
			$roles[ $key ] = translate_user_role( $label );
		}

		printf(
			'<select name="%1$s[]" id="%1$s" multiple class="wc-enhanced-select" style="min-width:320px" data-placeholder="%2$s">',
			esc_attr( $name ),
			esc_attr__( 'Todos los usuarios', 'imagina-woo-quotes' )
		);

		foreach ( $roles as $key => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $key ),
				in_array( $key, $value, true ) ? ' selected' : '',
				esc_html( $label )
			);
		}

		echo '</select>';
	}

	/**
	 * Pinta el selector de términos de una taxonomía.
	 *
	 * @param string $name     Atributo name.
	 * @param string $taxonomy Taxonomía.
	 * @param mixed  $value    Términos seleccionados.
	 * @return void
	 */
	private static function render_terms( $name, $taxonomy, $value ) {
		$value = array_map( 'absint', is_array( $value ) ? $value : array() );

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 500,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return;
		}

		printf(
			'<select name="%1$s[]" id="%1$s" multiple class="wc-enhanced-select" style="min-width:320px" data-placeholder="%2$s">',
			esc_attr( $name ),
			esc_attr__( 'Ninguna', 'imagina-woo-quotes' )
		);

		foreach ( $terms as $term ) {
			printf(
				'<option value="%1$d"%2$s>%3$s</option>',
				esc_attr( $term->term_id ),
				in_array( $term->term_id, $value, true ) ? ' selected' : '',
				esc_html( $term->name )
			);
		}

		echo '</select>';
	}

	/**
	 * Pinta el buscador de productos de WooCommerce.
	 *
	 * @param string $name  Atributo name.
	 * @param mixed  $value Productos seleccionados.
	 * @return void
	 */
	private static function render_products( $name, $value ) {
		$value = array_map( 'absint', is_array( $value ) ? $value : array() );

		printf(
			'<select name="%1$s[]" id="%1$s" multiple class="wc-product-search" style="min-width:320px" data-placeholder="%2$s" data-action="woocommerce_json_search_products_and_variations">',
			esc_attr( $name ),
			esc_attr__( 'Busca productos…', 'imagina-woo-quotes' )
		);

		foreach ( $value as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( $product ) {
				printf(
					'<option value="%1$d" selected>%2$s</option>',
					esc_attr( $product_id ),
					esc_html( wp_strip_all_tags( $product->get_formatted_name() ) )
				);
			}
		}

		echo '</select>';
	}

	/**
	 * Pinta el selector de imagen de la biblioteca de medios.
	 *
	 * @param string $name  Atributo name.
	 * @param mixed  $value ID del adjunto.
	 * @return void
	 */
	private static function render_media( $name, $value ) {
		$value = absint( $value );
		$url   = $value ? wp_get_attachment_image_url( $value, 'medium' ) : '';

		printf(
			'<div class="iwq-media-field"><input type="hidden" name="%1$s" id="%1$s" value="%2$d"><div class="iwq-media-field__preview">%3$s</div><button type="button" class="iwq-btn iwq-btn--secondary iwq-btn--sm iwq-media-select">%4$s</button> <button type="button" class="iwq-btn iwq-btn--ghost iwq-btn--sm iwq-media-clear"%5$s>%6$s</button></div>',
			esc_attr( $name ),
			esc_attr( $value ),
			$url ? '<img src="' . esc_url( $url ) . '" alt="">' : '',
			esc_html__( 'Elegir imagen', 'imagina-woo-quotes' ),
			$value ? '' : ' hidden',
			esc_html__( 'Quitar', 'imagina-woo-quotes' )
		);
	}

	/**
	 * Pinta el selector de diseño de email con su descripción.
	 *
	 * @param string $name  Atributo name.
	 * @param mixed  $value Diseño elegido.
	 * @return void
	 */
	private static function render_email_styles( $name, $value ) {
		$value = $value ? $value : 'moderno';

		echo '<fieldset class="iwq-style-picker">';

		foreach ( IWQ_Email_Styles::get_styles() as $key => $style ) {
			printf(
				'<label class="iwq-style-picker__option"><input type="radio" name="%1$s" value="%2$s"%3$s> <span class="iwq-style-picker__label">%4$s</span><span class="iwq-style-picker__desc">%5$s</span></label>',
				esc_attr( $name ),
				esc_attr( $key ),
				checked( $value, $key, false ),
				esc_html( $style['label'] ),
				esc_html( $style['description'] )
			);
		}

		echo '</fieldset>';
	}

	/**
	 * Pinta el constructor de formularios.
	 *
	 * @param string $name Atributo name base.
	 * @return void
	 */
	private static function render_form_builder( $name ) {
		iwq_get_template(
			'admin/form-builder.php',
			array(
				'name'   => $name,
				'fields' => iwq_get_all_form_fields(),
			)
		);
	}
}
