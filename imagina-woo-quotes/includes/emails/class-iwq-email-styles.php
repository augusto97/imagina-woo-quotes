<?php
/**
 * Diseños de email.
 *
 * Tres diseños con la misma estructura de contenido: el comerciante elige
 * uno en los ajustes y se aplica a los seis emails. El CSS se escribe en
 * línea por el inliner de WooCommerce, así que aquí solo hay selectores
 * simples que los clientes de correo entienden.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Email_Styles
 */
class IWQ_Email_Styles {

	/**
	 * Diseños disponibles.
	 *
	 * @return array<string,array{label:string,description:string}>
	 */
	public static function get_styles() {
		return array(
			'moderno'     => array(
				'label'       => __( 'Moderno', 'imagina-woo-quotes' ),
				'description' => __( 'Tarjeta blanca con barra de color, resumen destacado y botones grandes. Es el diseño por defecto.', 'imagina-woo-quotes' ),
			),
			'minimal'     => array(
				'label'       => __( 'Minimalista', 'imagina-woo-quotes' ),
				'description' => __( 'Texto sobre fondo blanco, sin tarjetas ni colores de fondo. Sobrio y muy legible.', 'imagina-woo-quotes' ),
			),
			'woocommerce' => array(
				'label'       => __( 'Como WooCommerce', 'imagina-woo-quotes' ),
				'description' => __( 'Usa la cabecera, el pie y los colores configurados en WooCommerce → Emails, para que encajen con el resto de emails de la tienda.', 'imagina-woo-quotes' ),
			),
		);
	}

	/**
	 * Diseño configurado.
	 *
	 * @return string
	 */
	public static function get_current() {
		$style = iwq_get_option( 'email_style', 'moderno' );

		return isset( self::get_styles()[ $style ] ) ? $style : 'moderno';
	}

	/**
	 * Color de acento.
	 *
	 * @return string
	 */
	public static function get_accent() {
		$color = iwq_get_option( 'email_accent', '' );

		if ( ! $color && 'woocommerce' === self::get_current() ) {
			$color = get_option( 'woocommerce_email_base_color', '#7f54b3' );
		}

		return $color ? $color : '#2563eb';
	}

	/**
	 * URL del logotipo, si hay.
	 *
	 * @return string
	 */
	public static function get_logo_url() {
		$id = (int) iwq_get_option( 'email_logo_id' );

		if ( ! $id ) {
			$id = (int) iwq_get_option( 'pdf_logo_id' );
		}

		if ( ! $id ) {
			$id = (int) get_theme_mod( 'custom_logo' );
		}

		$url = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';

		return $url ? $url : '';
	}

	/**
	 * Texto del pie.
	 *
	 * @return string
	 */
	public static function get_footer_text() {
		$text = iwq_get_option( 'email_footer_text', '' );

		if ( '' === $text ) {
			$text = get_bloginfo( 'name' );

			$address = WC()->countries->get_base_address();

			if ( $address ) {
				$text .= ' · ' . $address . ', ' . WC()->countries->get_base_city();
			}
		}

		return $text;
	}

	/**
	 * CSS del diseño, para que WooCommerce lo escriba en línea.
	 *
	 * @param string $style Diseño.
	 * @return string
	 */
	public static function get_css( $style = '' ) {
		$style  = $style ? $style : self::get_current();
		$accent = self::get_accent();
		$text   = '#1f2937';
		$muted  = '#6b7280';
		$border = '#e5e7eb';
		$soft   = '#f4f5f7';

		$common = "
			.iwq-email-body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: {$text}; font-size: 15px; line-height: 1.6; }
			.iwq-email-body p { margin: 0 0 14px; }
			.iwq-email-body a { color: {$accent}; }
			.iwq-h1 { font-size: 24px; line-height: 1.25; font-weight: 700; margin: 0 0 12px; color: {$text}; }
			.iwq-h2 { font-size: 16px; font-weight: 700; margin: 24px 0 10px; color: {$text}; }
			.iwq-muted { color: {$muted}; font-size: 13px; }
			.iwq-summary { width: 100%; border-collapse: collapse; margin: 18px 0; }
			.iwq-summary td { padding: 9px 12px; border-bottom: 1px solid {$border}; font-size: 14px; vertical-align: top; }
			.iwq-summary td.iwq-label { color: {$muted}; width: 40%; }
			.iwq-summary td.iwq-value { font-weight: 600; text-align: right; }
			.iwq-items { width: 100%; border-collapse: collapse; margin: 8px 0 4px; }
			.iwq-items th { text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; color: {$muted}; padding: 8px 10px; border-bottom: 2px solid {$border}; }
			.iwq-items th.iwq-num, .iwq-items td.iwq-num { text-align: right; white-space: nowrap; }
			.iwq-items td { padding: 12px 10px; border-bottom: 1px solid {$border}; vertical-align: middle; font-size: 14px; }
			.iwq-items img { width: 48px; height: 48px; border-radius: 6px; display: block; }
			.iwq-items .iwq-name { font-weight: 600; }
			.iwq-items .iwq-meta { color: {$muted}; font-size: 12px; }
			.iwq-items del { color: #9ca3af; font-size: 12px; }
			.iwq-totals { width: 100%; border-collapse: collapse; margin: 6px 0 12px; }
			.iwq-totals td { padding: 6px 10px; font-size: 14px; }
			.iwq-totals td.iwq-label { text-align: right; color: {$muted}; }
			.iwq-totals td.iwq-value { text-align: right; white-space: nowrap; width: 30%; }
			.iwq-totals tr.iwq-grand td { font-size: 17px; font-weight: 700; color: {$text}; border-top: 2px solid {$border}; padding-top: 10px; }
			.iwq-note { background: {$soft}; border-radius: 8px; padding: 12px 16px; margin: 14px 0; font-size: 14px; color: {$muted}; }
			.iwq-btn { display: inline-block; padding: 13px 26px; border-radius: 8px; font-weight: 700; font-size: 15px; text-decoration: none; }
			.iwq-btn-primary { background: {$accent}; color: #ffffff !important; }
			.iwq-btn-secondary { background: #ffffff; color: {$text} !important; border: 1px solid #d1d5db; }
			.iwq-cta { margin: 22px 0 8px; }
			.iwq-cta td { padding: 0 8px 8px 0; }
			.iwq-quote-box { border-left: 3px solid {$accent}; background: {$soft}; padding: 12px 16px; margin: 14px 0; border-radius: 0 8px 8px 0; }
			.iwq-quote-box .iwq-who { font-size: 12px; color: {$muted}; margin-top: 6px; }
			.iwq-form { width: 100%; border-collapse: collapse; margin: 6px 0; }
			.iwq-form td { padding: 8px 10px; border-bottom: 1px solid {$border}; font-size: 14px; vertical-align: top; }
			.iwq-form td.iwq-label { color: {$muted}; width: 38%; }
			.iwq-footer { color: {$muted}; font-size: 12px; text-align: center; padding: 22px 12px 0; line-height: 1.6; }
		";

		if ( 'moderno' === $style ) {
			return $common . "
				.iwq-email-outer { background: #eef0f3; padding: 32px 12px; }
				.iwq-email-card { background: #ffffff; border-radius: 14px; overflow: hidden; width: 100%; max-width: 620px; margin: 0 auto; }
				.iwq-email-accent { height: 6px; background: {$accent}; }
				.iwq-email-head { padding: 26px 32px 4px; }
				.iwq-email-head img { max-height: 44px; }
				.iwq-email-content { padding: 12px 32px 30px; }
				.iwq-summary { background: {$soft}; border-radius: 10px; }
				.iwq-summary td { border-bottom: 1px solid #e8eaee; }
			";
		}

		if ( 'minimal' === $style ) {
			return $common . "
				.iwq-email-outer { background: #ffffff; padding: 28px 12px; }
				.iwq-email-card { width: 100%; max-width: 600px; margin: 0 auto; }
				.iwq-email-head { padding: 0 0 18px; border-bottom: 1px solid {$border}; }
				.iwq-email-head img { max-height: 36px; }
				.iwq-email-content { padding: 22px 0 10px; }
				.iwq-h1 { font-weight: 600; letter-spacing: -0.01em; }
				.iwq-note { background: transparent; border: 1px solid {$border}; }
				.iwq-btn-primary { border-radius: 4px; }
				.iwq-btn-secondary { border-radius: 4px; }
			";
		}

		// Como WooCommerce: solo el contenido; la cabecera y el pie los pone WC.
		return $common . "
			.iwq-email-content { padding: 0; }
			.iwq-summary { background: {$soft}; border-radius: 6px; }
		";
	}
}
