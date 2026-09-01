<?php
/**
 * Integración con reCAPTCHA v2 y v3.
 *
 * El script de Google solo se carga si hay claves configuradas y solo en la
 * página del formulario, para no arrastrar ~90 KB de JavaScript de terceros
 * a todo el sitio.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Recaptcha
 */
class IWQ_Recaptcha {

	const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

	/**
	 * Indica si reCAPTCHA está configurado y activo.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return iwq_option_enabled( 'recaptcha_enabled' )
			&& iwq_get_option( 'recaptcha_site_key' )
			&& iwq_get_option( 'recaptcha_secret_key' );
	}

	/**
	 * Versión configurada: `v2` o `v3`.
	 *
	 * @return string
	 */
	public static function get_version() {
		return 'v3' === iwq_get_option( 'recaptcha_version', 'v3' ) ? 'v3' : 'v2';
	}

	/**
	 * URL del script de Google con la clave del sitio.
	 *
	 * @return string
	 */
	public static function get_script_url() {
		$args = array();

		if ( 'v3' === self::get_version() ) {
			$args['render'] = iwq_get_option( 'recaptcha_site_key' );
		}

		return add_query_arg( $args, 'https://www.google.com/recaptcha/api.js' );
	}

	/**
	 * Pinta el widget (v2) o el campo oculto del token (v3).
	 *
	 * @return string
	 */
	public static function render() {
		if ( ! self::is_enabled() ) {
			return '';
		}

		$site_key = iwq_get_option( 'recaptcha_site_key' );

		if ( 'v2' === self::get_version() ) {
			return sprintf(
				'<div class="iwq-recaptcha g-recaptcha" data-sitekey="%s"></div>',
				esc_attr( $site_key )
			);
		}

		return sprintf(
			'<input type="hidden" name="iwq_recaptcha_token" class="iwq-recaptcha-token" data-sitekey="%s" value="">',
			esc_attr( $site_key )
		);
	}

	/**
	 * Verifica el token contra la API de Google.
	 *
	 * @return true|WP_Error
	 */
	public static function verify() {
		if ( ! self::is_enabled() ) {
			return true;
		}

		$token = '';

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- el nonce lo comprueba quien llama.
		if ( isset( $_POST['iwq_recaptcha_token'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_POST['iwq_recaptcha_token'] ) );
		} elseif ( isset( $_POST['g-recaptcha-response'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) );
		}
		// phpcs:enable

		if ( ! $token ) {
			return new WP_Error( 'iwq_recaptcha_missing', __( 'Confirma que no eres un robot antes de enviar.', 'imagina-woo-quotes' ) );
		}

		$response = wp_remote_post(
			self::VERIFY_URL,
			array(
				'timeout' => 10,
				'body'    => array(
					'secret'   => iwq_get_option( 'recaptcha_secret_key' ),
					'response' => $token,
					'remoteip' => WC_Geolocation::get_ip_address(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			// Si Google no responde, dejamos pasar la solicitud: perder un
			// presupuesto real es peor que aceptar algún envío automático.
			wc_get_logger()->warning(
				'No se pudo verificar reCAPTCHA: ' . $response->get_error_message(),
				array( 'source' => 'imagina-woo-quotes' )
			);

			return true;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['success'] ) ) {
			return new WP_Error( 'iwq_recaptcha_failed', __( 'No hemos podido verificar que seas una persona. Inténtalo de nuevo.', 'imagina-woo-quotes' ) );
		}

		if ( 'v3' === self::get_version() ) {
			$threshold = (float) iwq_get_option( 'recaptcha_threshold', 0.5 );
			$score     = isset( $body['score'] ) ? (float) $body['score'] : 1;

			if ( $score < $threshold ) {
				return new WP_Error( 'iwq_recaptcha_score', __( 'Tu envío se ha marcado como sospechoso. Si es un error, escríbenos.', 'imagina-woo-quotes' ) );
			}
		}

		return true;
	}
}
