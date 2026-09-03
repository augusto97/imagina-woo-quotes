<?php
/**
 * Pestaña de inicio del panel: métricas, lista de puesta en marcha y accesos.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Dashboard
 */
class IWQ_Dashboard {

	/**
	 * Comprobaciones de puesta en marcha.
	 *
	 * Cada entrada indica si está resuelta, qué es y dónde arreglarla. Son
	 * lecturas de opciones y una consulta ligera, nada que pese en la carga.
	 *
	 * @return array<int,array{ok:bool,label:string,help:string,url:string,action:string}>
	 */
	public static function get_checklist() {
		$settings = admin_url( 'admin.php?page=iwq-settings&tab=' );
		$page_id  = (int) iwq_get_option( 'quote_page_id' );
		$page     = $page_id ? get_post( $page_id ) : null;

		$email_settings = get_option( 'woocommerce_iwq_new_request_settings' );
		$email_settings = is_array( $email_settings ) ? $email_settings : array();
		$emails_enabled = empty( $email_settings['enabled'] ) || 'yes' === $email_settings['enabled'];
		$admin_email    = ! empty( $email_settings['recipient'] ) ? $email_settings['recipient'] : get_option( 'admin_email' );

		$items = array(
			array(
				'ok'     => iwq_option_enabled( 'enabled' ),
				'label'  => __( 'Sistema de presupuestos activo', 'imagina-woo-quotes' ),
				'help'   => __( 'Mientras esté apagado no se muestra ningún botón en la tienda.', 'imagina-woo-quotes' ),
				'url'    => $settings . 'general',
				'action' => __( 'Activar', 'imagina-woo-quotes' ),
			),
			array(
				'ok'     => $page && 'publish' === $page->post_status,
				'label'  => __( 'Página de solicitud publicada', 'imagina-woo-quotes' ),
				'help'   => $page
					? sprintf( /* translators: %s: page title */ __( 'Los clientes envían el formulario desde «%s».', 'imagina-woo-quotes' ), $page->post_title )
					: __( 'Elige la página que contiene la lista de presupuesto y el formulario.', 'imagina-woo-quotes' ),
				'url'    => $settings . 'general',
				'action' => __( 'Elegir página', 'imagina-woo-quotes' ),
			),
			array(
				'ok'     => IWQ_PDF::is_available(),
				'label'  => __( 'Generador de PDF disponible', 'imagina-woo-quotes' ),
				'help'   => IWQ_PDF::is_available()
					? __( 'Cada presupuesto lleva su documento adjunto.', 'imagina-woo-quotes' )
					: __( 'Falta la librería dompdf: instala el zip de la versión publicada o ejecuta composer install.', 'imagina-woo-quotes' ),
				'url'    => $settings . 'pdf',
				'action' => __( 'Ver ajustes', 'imagina-woo-quotes' ),
			),
			array(
				'ok'     => (bool) iwq_get_option( 'pdf_template_id' ),
				'label'  => __( 'Plantilla de PDF elegida', 'imagina-woo-quotes' ),
				'help'   => __( 'Sin plantilla se usa el diseño por defecto; puedes editarla con el editor de bloques.', 'imagina-woo-quotes' ),
				'url'    => $settings . 'pdf',
				'action' => __( 'Elegir plantilla', 'imagina-woo-quotes' ),
			),
			array(
				'ok'     => $emails_enabled,
				'label'  => __( 'Aviso de nueva solicitud activo', 'imagina-woo-quotes' ),
				'help'   => sprintf( /* translators: %s: email address */ __( 'Las solicitudes nuevas llegan a %s.', 'imagina-woo-quotes' ), $admin_email ),
				'url'    => admin_url( 'admin.php?page=wc-settings&tab=email&section=iwq_new_request' ),
				'action' => __( 'Configurar', 'imagina-woo-quotes' ),
			),
			array(
				'ok'     => (bool) iwq_get_option( 'recaptcha_site_key' ) || ! iwq_option_enabled( 'allow_guests' ),
				'label'  => __( 'Formulario protegido contra spam', 'imagina-woo-quotes' ),
				'help'   => __( 'Con visitantes sin cuenta conviene activar reCAPTCHA además del límite por IP.', 'imagina-woo-quotes' ),
				'url'    => $settings . 'form',
				'action' => __( 'Añadir reCAPTCHA', 'imagina-woo-quotes' ),
			),
		);

		return $items;
	}

	/**
	 * Últimas solicitudes, para la tabla de actividad.
	 *
	 * @param int $limit Cuántas.
	 * @return WC_Order[]
	 */
	public static function get_recent( $limit = 6 ) {
		return wc_get_orders(
			array(
				'status'  => iwq_get_quote_statuses(),
				'limit'   => $limit,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);
	}

	/**
	 * URL del listado de pedidos filtrado por estado, con y sin HPOS.
	 *
	 * @param string $status Estado sin prefijo, vacío para todos.
	 * @return string
	 */
	public static function get_orders_url( $status = '' ) {
		$hpos = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

		$url = $hpos ? admin_url( 'admin.php?page=wc-orders' ) : admin_url( 'edit.php?post_type=shop_order' );

		return $status ? add_query_arg( 'status', 'wc-' . $status, $url ) : $url;
	}

	/**
	 * Pinta la pestaña.
	 *
	 * @return void
	 */
	public static function render() {
		iwq_get_template(
			'admin/dashboard.php',
			array(
				'data'      => IWQ_Statistics::get_data( 30 ),
				'checklist' => self::get_checklist(),
				'recent'    => self::get_recent(),
			)
		);
	}
}
