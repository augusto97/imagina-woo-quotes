<?php
/**
 * Instalación y desinstalación.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Install
 */
class IWQ_Install {

	/**
	 * Rutina de activación.
	 *
	 * @return void
	 */
	public static function activate() {
		self::create_pages();
		self::add_default_options();
		self::add_capabilities();
		self::create_upload_dir();

		// Los endpoints de Mi Cuenta necesitan que se reescriban las reglas.
		update_option( 'iwq_flush_rewrite_rules', 'yes' );
		update_option( 'iwq_version', IWQ_VERSION );
	}

	/**
	 * Rutina de desactivación: limpia los crons.
	 *
	 * @return void
	 */
	public static function deactivate() {
		foreach ( array( 'iwq_check_expiration', 'iwq_send_reminders', 'iwq_clean_sessions' ) as $hook ) {
			$timestamp = wp_next_scheduled( $hook );

			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}
	}

	/**
	 * Crea las páginas que el plugin necesita si aún no existen.
	 *
	 * @return void
	 */
	private static function create_pages() {
		$pages = array(
			'quote_page_id' => array(
				'title'   => __( 'Solicitar presupuesto', 'imagina-woo-quotes' ),
				'slug'    => 'solicitar-presupuesto',
				'content' => '<!-- wp:shortcode -->[iwq_quote_list]<!-- /wp:shortcode -->',
			),
		);

		foreach ( $pages as $option => $page ) {
			$existing = (int) iwq_get_option( $option );

			if ( $existing && 'page' === get_post_type( $existing ) && 'trash' !== get_post_status( $existing ) ) {
				continue;
			}

			$page_id = wp_insert_post(
				array(
					'post_title'     => $page['title'],
					'post_name'      => $page['slug'],
					'post_content'   => $page['content'],
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				)
			);

			if ( $page_id && ! is_wp_error( $page_id ) ) {
				iwq_update_option( $option, $page_id );
			}
		}
	}

	/**
	 * Valores por defecto de las opciones de comportamiento.
	 *
	 * Es la única lista: la activación los siembra, `iwq_get_option()` los
	 * devuelve cuando la opción no existe y la página de ajustes los pinta.
	 * Así el interruptor del admin y lo que hace el front nunca discrepan.
	 * Los de diseño viven en IWQ_Design::get_defaults().
	 *
	 * @return array<string,mixed>
	 */
	public static function get_default_options() {
		return array(
			// Comportamiento general.
			'enabled'                  => 'yes',
			'allow_guests'             => 'yes',
			'rate_limit'               => 5,
			'hide_price'               => 'no',
			'hide_price_text'          => __( 'Precio bajo consulta', 'imagina-woo-quotes' ),
			'hide_add_to_cart'         => 'no',
			'button_label'             => __( 'Solicitar presupuesto', 'imagina-woo-quotes' ),
			'button_label_added'       => __( 'Ya está en tu presupuesto', 'imagina-woo-quotes' ),
			'button_position_single'   => 'after_add_to_cart',
			'show_on_shop'             => 'yes',
			'show_on_product'          => 'yes',
			'show_on_cart'             => 'no',
			'open_drawer_after_add'    => 'yes',
			'redirect_after_add'       => 'no',
			// Presupuesto.
			'expiry_days'              => 7,
			'auto_expire'              => 'yes',
			'reminder_days_before'     => 2,
			'reminders_enabled'        => 'yes',
			'allow_counter_offers'     => 'yes',
			'redirect_to_payment'      => 'yes',
			'allow_customer_notes'     => 'yes',
			'allow_quantity_change'    => 'yes',
			'min_quantity'             => 0,
			'max_quantity'             => 0,
			'sale_date'                => 'paid',
			'hide_from_all_orders'     => 'no',
			// Formulario.
			'form_title'               => __( 'Cuéntanos qué necesitas', 'imagina-woo-quotes' ),
			'submit_label'             => __( 'Enviar solicitud', 'imagina-woo-quotes' ),
			'success_message'          => __( '¡Gracias! Hemos recibido tu solicitud y te responderemos en breve.', 'imagina-woo-quotes' ),
			'show_form_when_empty'     => 'no',
			'autocomplete_form'        => 'yes',
			'recaptcha_enabled'        => 'no',
			'recaptcha_version'        => 'v3',
			'recaptcha_threshold'      => 0.5,
			'upload_max_size'          => 5,
			'form_fields'              => iwq_get_default_form_fields(),
			'form_privacy_text'        => __( 'Tus datos se usarán para procesar tu solicitud y responderte. Consulta nuestra [privacy_policy].', 'imagina-woo-quotes' ),
			// PDF.
			'pdf_enabled'              => 'yes',
			'pdf_attach_to_email'      => 'yes',
			'pdf_paper_size'           => 'A4',
			'pdf_orientation'          => 'portrait',
			'pdf_font'                 => 'DejaVu Sans',
			'pdf_filename'             => 'presupuesto-{order_number}',
			'pdf_show_actions'         => 'yes',
			'pdf_show_strikethrough'   => 'yes',
			'pdf_show_footer'          => 'yes',
			// Emails.
			'email_style'              => 'moderno',
			// Reglas.
			'scope'                    => 'all',
			'stock_rule'               => 'any',
			'allow_external_grouped'   => 'no',
			'empty_cart_after_transfer' => 'no',
		);
	}

	/**
	 * Completa la instalación cuando el plugin se actualizó sin pasar por la
	 * activación (subida por FTP, actualizadores que activan en silencio,
	 * WP-CLI): siembra las opciones nuevas, las capacidades y la carpeta de
	 * adjuntos. Se ejecuta una vez por versión.
	 *
	 * @return void
	 */
	public static function maybe_update() {
		if ( IWQ_VERSION === get_option( 'iwq_version' ) ) {
			return;
		}

		self::add_default_options();
		self::add_capabilities();
		self::create_upload_dir();

		update_option( 'iwq_version', IWQ_VERSION );
	}

	/**
	 * Siembra las opciones por defecto sin pisar las que ya existan.
	 *
	 * @return void
	 */
	private static function add_default_options() {
		foreach ( self::get_default_options() as $key => $value ) {
			if ( false === get_option( 'iwq_' . $key, false ) ) {
				add_option( 'iwq_' . $key, $value );
			}
		}
	}

	/**
	 * Da al administrador y al gestor de tienda las capacidades del plugin.
	 *
	 * @return void
	 */
	private static function add_capabilities() {
		$caps = array(
			'manage_iwq_quotes',
			'edit_iwq_pdf_template',
			'read_iwq_pdf_template',
			'delete_iwq_pdf_template',
			'edit_iwq_pdf_templates',
			'edit_others_iwq_pdf_templates',
			'publish_iwq_pdf_templates',
			'read_private_iwq_pdf_templates',
			'delete_iwq_pdf_templates',
			'delete_private_iwq_pdf_templates',
			'delete_published_iwq_pdf_templates',
			'delete_others_iwq_pdf_templates',
			'edit_private_iwq_pdf_templates',
			'edit_published_iwq_pdf_templates',
		);

		foreach ( array( 'administrator', 'shop_manager' ) as $role_name ) {
			$role = get_role( $role_name );

			if ( ! $role ) {
				continue;
			}

			foreach ( $caps as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}

	/**
	 * Crea el directorio protegido donde se guardan los adjuntos del formulario.
	 *
	 * Se bloquea el acceso directo por HTTP: los archivos subidos por clientes
	 * solo deben servirse a través del endpoint de descarga, que comprueba
	 * permisos.
	 *
	 * @return void
	 */
	private static function create_upload_dir() {
		$dir = IWQ_Uploads::get_base_dir();

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$htaccess = trailingslashit( $dir ) . '.htaccess';

		if ( ! file_exists( $htaccess ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			@file_put_contents( $htaccess, "Order deny,allow\nDeny from all\n" );
		}

		$index = trailingslashit( $dir ) . 'index.html';

		if ( ! file_exists( $index ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			@file_put_contents( $index, '' );
		}
	}
}
