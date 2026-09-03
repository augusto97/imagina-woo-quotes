<?php
/**
 * Diseño del front configurable desde el admin.
 *
 * La hoja de estilos del front es estática y declara todo con variables
 * CSS. Aquí se traducen los ajustes de la pestaña «Diseño» a un bloque
 * pequeño de variables que se imprime en línea tras esa hoja: unas decenas
 * de bytes, sin archivos generados ni consultas extra (las opciones van en
 * la carga automática de WordPress).
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Design
 */
class IWQ_Design {

	/**
	 * Valores por defecto de cada ajuste de diseño.
	 *
	 * Un valor vacío o igual al defecto no genera CSS: la hoja estática ya
	 * lo cubre.
	 *
	 * @return array<string,string>
	 */
	public static function get_defaults() {
		return array(
			'design_accent'           => '#2563eb',
			'design_accent_hover'     => '#1d4ed8',
			'design_accent_contrast'  => '#ffffff',
			'design_text'             => '#1f2937',
			'design_text_muted'       => '#6b7280',
			'design_surface'          => '#ffffff',
			'design_surface_alt'      => '#f9fafb',
			'design_border'           => '#e5e7eb',
			'design_radius'           => '8',
			'design_dark_mode'        => 'no',
			'button_style'            => 'solid',
			'button_radius'           => '',
			'button_padding_y'        => '',
			'button_padding_x'        => '',
			'button_font_size'        => '',
			'button_font_weight'      => 'inherit',
			'button_text_transform'   => 'none',
			'button_font'             => 'inherit',
			'button_shadow'           => 'no',
			'button_full_width'       => 'no',
			'link_color'              => '',
			'link_hover_color'        => '',
			'drawer_position'         => 'right',
			'drawer_width'            => '420',
			'drawer_title'            => '',
			'drawer_header_style'     => 'plain',
			'drawer_overlay'          => '45',
			'drawer_show_thumbs'      => 'yes',
			'drawer_footer_label'     => '',
			'page_list_style'         => 'woocommerce',
			'page_layout'             => 'stacked',
			'page_width'              => 'auto',
			'page_columns'            => '40',
			'page_columns_gap'        => '48',
			'page_sticky_list'        => 'yes',
			'page_card_style'         => 'plain',
			'page_show_thumbs'        => 'yes',
			'page_list_title'         => '',
			'field_style'             => 'default',
			'field_radius'            => '',
			'custom_css'              => '',
		);
	}

	/**
	 * Lee un ajuste de diseño con su valor por defecto.
	 *
	 * @param string $key Clave sin prefijo.
	 * @return string
	 */
	public static function get( $key ) {
		$defaults = self::get_defaults();
		$value    = get_option( 'iwq_' . $key, '' );

		if ( '' === $value || null === $value || false === $value ) {
			return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
		}

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * CSS en línea que ajusta las variables del front.
	 *
	 * @return string
	 */
	public static function get_css() {
		$defaults = self::get_defaults();
		$vars     = array();

		$colors = array(
			'design_accent'          => '--iwq-accent',
			'design_accent_hover'    => '--iwq-accent-hover',
			'design_accent_contrast' => '--iwq-accent-contrast',
			'design_text'            => '--iwq-text',
			'design_text_muted'      => '--iwq-text-muted',
			'design_surface'         => '--iwq-surface',
			'design_surface_alt'     => '--iwq-surface-alt',
			'design_border'          => '--iwq-border',
			'link_color'             => '--iwq-link',
			'link_hover_color'       => '--iwq-link-hover',
		);

		foreach ( $colors as $key => $var ) {
			$value = sanitize_hex_color( self::get( $key ) );

			if ( $value && $value !== $defaults[ $key ] ) {
				$vars[ $var ] = $value;
			}
		}

		$sizes = array(
			'design_radius'    => '--iwq-radius',
			'button_radius'    => '--iwq-btn-radius',
			'button_padding_y' => '--iwq-btn-pad-y',
			'button_padding_x' => '--iwq-btn-pad-x',
			'button_font_size' => '--iwq-btn-size',
			'field_radius'     => '--iwq-field-radius',
			'drawer_width'     => '--iwq-drawer-width',
		);

		foreach ( $sizes as $key => $var ) {
			$raw = self::get( $key );

			if ( '' === $raw || $raw === $defaults[ $key ] ) {
				continue;
			}

			$vars[ $var ] = absint( $raw ) . 'px';
		}

		// Reparto de las columnas: porcentaje de la lista; el resto, formulario.
		$list_share = absint( self::get( 'page_columns' ) );

		if ( $list_share >= 20 && $list_share <= 80 && (string) $list_share !== $defaults['page_columns'] ) {
			$vars['--iwq-col-list'] = $list_share . 'fr';
			$vars['--iwq-col-form'] = ( 100 - $list_share ) . 'fr';
		}

		$gap = self::get( 'page_columns_gap' );

		if ( '' !== $gap && $gap !== $defaults['page_columns_gap'] ) {
			$vars['--iwq-col-gap'] = absint( $gap ) . 'px';
		}

		$weight = self::get( 'button_font_weight' );

		if ( in_array( $weight, array( '400', '500', '600', '700' ), true ) ) {
			$vars['--iwq-btn-weight'] = $weight;
		}

		if ( 'uppercase' === self::get( 'button_text_transform' ) ) {
			$vars['--iwq-btn-transform'] = 'uppercase';
			$vars['--iwq-btn-spacing']   = '0.04em';
		}

		if ( 'system' === self::get( 'button_font' ) ) {
			$vars['--iwq-btn-font'] = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
		}

		if ( 'yes' === self::get( 'button_shadow' ) ) {
			$vars['--iwq-btn-shadow'] = '0 6px 18px rgba( 0, 0, 0, 0.16 )';
		}

		$overlay = self::get( 'drawer_overlay' );

		if ( '' !== $overlay && $overlay !== $defaults['drawer_overlay'] ) {
			$vars['--iwq-overlay'] = 'rgba( 0, 0, 0, ' . ( min( 100, absint( $overlay ) ) / 100 ) . ' )';
		}

		$css = '';

		if ( $vars ) {
			$declarations = '';

			foreach ( $vars as $var => $value ) {
				$declarations .= $var . ':' . $value . ';';
			}

			$css .= '.iwq,:root{' . $declarations . '}';
		}

		if ( 'yes' === self::get( 'design_dark_mode' ) ) {
			$css .= '@media (prefers-color-scheme:dark){.iwq,:root{--iwq-text:#e5e7eb;--iwq-text-muted:#9ca3af;--iwq-surface:#1f2937;--iwq-surface-alt:#111827;--iwq-border:#374151;--iwq-shadow:0 10px 40px rgba(0,0,0,0.5);}}';
		}

		$custom = self::get( 'custom_css' );

		if ( '' !== $custom ) {
			$css .= "\n" . self::sanitize_css( $custom );
		}

		/**
		 * Filtra el CSS generado a partir de los ajustes de diseño.
		 *
		 * @param string $css CSS en línea.
		 */
		return (string) apply_filters( 'iwq_design_css', $css );
	}

	/**
	 * Limpia CSS escrito por el administrador: sin etiquetas ni cierres de
	 * <style> que permitan salir del bloque.
	 *
	 * @param string $css CSS recibido.
	 * @return string
	 */
	public static function sanitize_css( $css ) {
		$css = wp_strip_all_tags( (string) $css );
		$css = str_ireplace( array( '</style', '<script', 'javascript:', 'expression(' ), '', $css );

		return trim( $css );
	}

	/**
	 * Clases modificadoras del botón según los ajustes.
	 *
	 * @return string[]
	 */
	public static function get_button_classes() {
		$classes = array();

		if ( 'outline' === self::get( 'button_style' ) ) {
			$classes[] = 'iwq-add-button--outline';
		}

		if ( 'yes' === self::get( 'button_full_width' ) ) {
			$classes[] = 'iwq-add-button--block';
		}

		return $classes;
	}

	/**
	 * Clases modificadoras del panel lateral.
	 *
	 * @return string[]
	 */
	public static function get_drawer_classes() {
		$classes = array();

		if ( 'left' === self::get( 'drawer_position' ) ) {
			$classes[] = 'iwq-drawer--left';
		}

		if ( 'accent' === self::get( 'drawer_header_style' ) ) {
			$classes[] = 'iwq-drawer--accent-header';
		}

		if ( 'no' === self::get( 'drawer_show_thumbs' ) ) {
			$classes[] = 'iwq-no-thumbs';
		}

		return $classes;
	}

	/**
	 * Clases de botón del tema, como las usa el carrito de WooCommerce.
	 *
	 * En temas de bloques WooCommerce añade la clase de botón del tema
	 * (wp-element-button) además de `button`; sin ella el tema no lo pinta.
	 *
	 * @param bool $alt Si es el botón principal (`alt`).
	 * @return string
	 */
	public static function get_theme_button_class( $alt = false ) {
		$classes = array( 'button' );

		if ( $alt ) {
			$classes[] = 'alt';
		}

		if ( function_exists( 'wc_wp_theme_get_element_class_name' ) ) {
			$theme = wc_wp_theme_get_element_class_name( 'button' );

			if ( $theme ) {
				$classes[] = $theme;
			}
		}

		return implode( ' ', $classes );
	}

	/**
	 * Clases modificadoras de la página de solicitud.
	 *
	 * @param string $align Alineación pedida por el bloque o el shortcode
	 *                      (content, wide o full); vacío usa el ajuste.
	 * @return string[]
	 */
	public static function get_page_classes( $align = '' ) {
		$classes = array();
		$layout  = self::get( 'page_layout' );
		$columns = 'columns' === $layout || 'columns_form_left' === $layout;

		// Con la tabla del carrito, los botones también son los del tema.
		if ( 'plugin' !== self::get( 'page_list_style' ) ) {
			$classes[] = 'woocommerce';
			$classes[] = 'iwq-quote-page--woo';
			$classes[] = 'iwq-theme-buttons';
		}

		if ( $columns ) {
			$classes[] = 'iwq-quote-page--columns';

			if ( 'yes' === self::get( 'page_sticky_list' ) ) {
				$classes[] = 'iwq-quote-page--sticky';
			}
		}

		if ( 'columns_form_left' === $layout ) {
			$classes[] = 'iwq-quote-page--form-left';
		}

		// Ancho: en escritorio dos columnas necesitan más sitio del que da
		// el ancho de contenido de la mayoría de temas, así que «auto» pasa
		// a ancho amplio cuando hay columnas. Las clases alignwide y
		// alignfull las entienden los temas de bloques y los clásicos con
		// soporte de alineaciones anchas.
		$width = in_array( $align, array( 'content', 'wide', 'full' ), true ) ? $align : self::get( 'page_width' );

		if ( 'auto' === $width ) {
			$width = $columns ? 'wide' : 'content';
		}

		if ( 'wide' === $width ) {
			$classes[] = 'alignwide';
			$classes[] = 'iwq-quote-page--wide';
		} elseif ( 'full' === $width ) {
			$classes[] = 'alignfull';
			$classes[] = 'iwq-quote-page--full';
		}

		$card = self::get( 'page_card_style' );

		if ( 'bordered' === $card || 'shadow' === $card ) {
			$classes[] = 'iwq-quote-page--card';
		}

		if ( 'shadow' === $card ) {
			$classes[] = 'iwq-quote-page--shadow';
		}

		if ( 'no' === self::get( 'page_show_thumbs' ) ) {
			$classes[] = 'iwq-no-thumbs';
		}

		$field = self::get( 'field_style' );

		if ( 'filled' === $field || 'underline' === $field ) {
			$classes[] = 'iwq-fields--' . $field;
		}

		return $classes;
	}
}
