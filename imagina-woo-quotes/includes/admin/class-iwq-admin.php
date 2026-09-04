<?php
/**
 * Integración general con el administrador.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Admin
 */
class IWQ_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_notices', array( $this, 'remove_lost_connection_notice' ), 0 );
		add_filter( 'plugin_action_links_' . IWQ_BASENAME, array( $this, 'action_links' ) );

		// Columna del contador de solicitudes en el listado de productos.
		add_filter( 'manage_edit-product_columns', array( $this, 'add_product_column' ), 20 );
		add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_column' ), 10, 2 );
		add_filter( 'manage_edit-product_sortable_columns', array( $this, 'make_column_sortable' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_by_requests' ) );

		// Acciones del listado de pedidos.
		add_filter( 'woocommerce_order_actions', array( $this, 'add_order_actions' ) );
		add_action( 'woocommerce_order_action_iwq_send_quote', array( $this, 'action_send_quote' ) );
		add_action( 'woocommerce_order_action_iwq_regenerate_pdf', array( $this, 'action_regenerate_pdf' ) );
	}

	/**
	 * Añade las páginas del plugin bajo el menú de WooCommerce.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Presupuestos', 'imagina-woo-quotes' ),
			__( 'Presupuestos', 'imagina-woo-quotes' ),
			'manage_woocommerce',
			'iwq-settings',
			array( IWQ_Settings::class, 'render_page' )
		);
	}

	/**
	 * Retira de nuestra pantalla el aviso «Connection lost» de WooCommerce.
	 *
	 * WooCommerce imprime en sus pantallas un aviso oculto que el latido de
	 * WordPress destapa cuando admin-ajax falla; algunos gestores de avisos
	 * lo muestran siempre. Nuestros ajustes se guardan con un envío normal
	 * del formulario y no dependen del latido, así que aquí solo confunde.
	 *
	 * @return void
	 */
	public function remove_lost_connection_notice() {
		$screen = get_current_screen();

		if ( ! $screen || false === strpos( $screen->id, 'iwq-settings' ) ) {
			return;
		}

		global $wp_filter;

		if ( empty( $wp_filter['admin_notices'] ) ) {
			return;
		}

		foreach ( $wp_filter['admin_notices']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'];

				if ( is_array( $function ) && isset( $function[1] ) && 'render_lost_connection_notice' === $function[1] ) {
					remove_action( 'admin_notices', $function, $priority );
				}
			}
		}
	}

	/**
	 * Carga los assets del admin solo en nuestras pantallas.
	 *
	 * @param string $hook Identificador de la pantalla.
	 * @return void
	 */
	public function enqueue( $hook ) {
		$screen = get_current_screen();

		// La pantalla del plugin lleva su propia interfaz: hoja de estilos y
		// script completos, que ninguna otra página del admin carga.
		if ( false !== strpos( $hook, 'iwq-settings' ) ) {
			wp_enqueue_style( 'iwq-admin', IWQ_URL . 'assets/css/admin.css', array(), IWQ_VERSION );

			wp_enqueue_script(
				'iwq-admin',
				IWQ_URL . 'assets/js/admin.js',
				array( 'jquery', 'jquery-ui-sortable', 'wp-i18n', 'wp-color-picker' ),
				IWQ_VERSION,
				true
			);

			wp_localize_script(
				'iwq-admin',
				'iwqAdmin',
				array(
					'fieldTypes'   => iwq_get_form_field_types(),
					'widths'       => iwq_get_form_field_widths(),
					'connectable'  => iwq_get_connectable_fields(),
					'i18n'         => array(
						'confirmDelete' => __( '¿Seguro que quieres borrar este campo?', 'imagina-woo-quotes' ),
						'newField'      => __( 'Campo nuevo', 'imagina-woo-quotes' ),
						'coreField'     => __( 'Este campo es del sistema: puedes cambiar su etiqueta, pero no borrarlo.', 'imagina-woo-quotes' ),
						'noOrder'       => __( 'Elige un presupuesto o crea uno de ejemplo para ver la vista previa.', 'imagina-woo-quotes' ),
						'noAttachments' => __( 'ninguno', 'imagina-woo-quotes' ),
						'sending'       => __( 'Enviando…', 'imagina-woo-quotes' ),
						'creating'      => __( 'Creando el presupuesto de ejemplo…', 'imagina-woo-quotes' ),
						'sampleCreated' => __( 'Presupuesto de ejemplo creado.', 'imagina-woo-quotes' ),
						'requestFailed' => __( 'La petición ha fallado. Revisa el registro de errores de PHP', 'imagina-woo-quotes' ),
						'noPdf'         => __( 'Este email no lleva PDF adjunto o la generación de PDF no está disponible.', 'imagina-woo-quotes' ),
						'saved'         => __( 'Ajustes guardados.', 'imagina-woo-quotes' ),
						'unsaved'       => __( 'Hay cambios sin guardar', 'imagina-woo-quotes' ),
						'noChanges'     => __( 'Sin cambios pendientes', 'imagina-woo-quotes' ),
						'saving'        => __( 'Guardando…', 'imagina-woo-quotes' ),
						'leave'         => __( 'Tienes cambios sin guardar. ¿Salir de todos modos?', 'imagina-woo-quotes' ),
					),
				)
			);

			wp_enqueue_media();
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_style( 'woocommerce_admin_styles' );

			return;
		}

		// En pedidos y productos solo hace falta un puñado de reglas para los
		// metaboxes: un archivo aparte de un kilobyte, sin JavaScript.
		$order_screens = array( 'shop_order', 'woocommerce_page_wc-orders', 'edit-product' );

		if ( $screen && in_array( $screen->id, $order_screens, true ) ) {
			wp_enqueue_style( 'iwq-admin-order', IWQ_URL . 'assets/css/admin-order.css', array(), IWQ_VERSION );
		}
	}

	/**
	 * Icono SVG en línea para la navegación del panel.
	 *
	 * Trazos de 24×24 con `currentColor`: pesan menos que una fuente de iconos
	 * y heredan el color del enlace.
	 *
	 * @param string $name Nombre del icono.
	 * @return string SVG listo para imprimir.
	 */
	public static function icon( $name ) {
		$paths = array(
			'home'    => '<path d="M3 11 12 3l9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
			'book'    => '<path d="M4 4h6a3 3 0 0 1 3 3v13a2 2 0 0 0-2-2H4z"/><path d="M20 4h-6a3 3 0 0 0-3 3v13a2 2 0 0 1 2-2h7z"/>',
			'chart'   => '<path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M22 20H2"/>',
			'eye'     => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
			'sliders' => '<path d="M4 6h10"/><path d="M18 6h2"/><path d="M4 12h2"/><path d="M10 12h10"/><path d="M4 18h12"/><path d="M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="8" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>',
			'tag'     => '<path d="M20 12 12 20l-9-9V3h8l9 9z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
			'file'    => '<path d="M14 3H6v18h12V7z"/><path d="M14 3v4h4"/><path d="M9 13h6"/><path d="M9 17h6"/>',
			'form'    => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 9h10"/><path d="M7 13h6"/><path d="M7 17h3"/>',
			'pdf'     => '<path d="M14 3H6v18h12V7z"/><path d="M14 3v4h4"/><path d="M8.5 16v-5h1.6a1.5 1.5 0 0 1 0 3H8.5"/><path d="M13 11h2v5h-2z"/>',
			'mail'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
			'filter'  => '<path d="M3 5h18l-7 8v6l-4-2v-4z"/>',
			'palette' => '<path d="M12 3a9 9 0 0 0 0 18c1.5 0 2-1 2-2 0-.6-.4-1-.6-1.5-.3-.6.1-1.5 1.1-1.5H16a5 5 0 0 0 5-5c0-4.4-4-8-9-8z"/><circle cx="7.5" cy="11.5" r="1"/><circle cx="10.5" cy="7.5" r="1"/><circle cx="15" cy="8" r="1"/>',
			'save'    => '<path d="M5 3h11l3 3v15H5z"/><path d="M8 3v5h7V3"/><path d="M8 21v-7h8v7"/>',
			'check'   => '<path d="m5 12 5 5L20 7"/>',
			'plus'    => '<path d="M12 5v14"/><path d="M5 12h14"/>',
			'orders'  => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6"/><path d="M9 12h6"/>',
			'blocks'  => '<rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/>',
			'arrow'   => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
		);

		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}

		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
	}

	/**
	 * Enlaces bajo el nombre del plugin en la lista de plugins.
	 *
	 * @param string[] $links Enlaces actuales.
	 * @return string[]
	 */
	public function action_links( $links ) {
		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=iwq-settings' ) ),
				esc_html__( 'Ajustes', 'imagina-woo-quotes' )
			),
			sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( IWQ_DOCS_URL ),
				esc_html__( 'Documentación', 'imagina-woo-quotes' )
			)
		);

		return $links;
	}

	/* ---------------------------------------------------------------------
	 * Contador de solicitudes por producto
	 * ------------------------------------------------------------------ */

	/**
	 * Añade la columna «Presupuestos» al listado de productos.
	 *
	 * @param array<string,string> $columns Columnas actuales.
	 * @return array<string,string>
	 */
	public function add_product_column( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'price' === $key ) {
				$new['iwq_requests'] = __( 'Presupuestos', 'imagina-woo-quotes' );
			}
		}

		return isset( $new['iwq_requests'] ) ? $new : $new + array( 'iwq_requests' => __( 'Presupuestos', 'imagina-woo-quotes' ) );
	}

	/**
	 * Pinta el valor de la columna.
	 *
	 * @param string $column  Columna que se está pintando.
	 * @param int    $post_id ID del producto.
	 * @return void
	 */
	public function render_product_column( $column, $post_id ) {
		if ( 'iwq_requests' !== $column ) {
			return;
		}

		$count = (int) get_post_meta( $post_id, '_iwq_request_count', true );

		if ( ! $count ) {
			echo '<span class="iwq-muted">—</span>';
			return;
		}

		printf(
			'<strong>%s</strong>',
			esc_html( number_format_i18n( $count ) )
		);
	}

	/**
	 * Declara la columna como ordenable.
	 *
	 * @param array<string,string> $columns Columnas ordenables.
	 * @return array<string,string>
	 */
	public function make_column_sortable( $columns ) {
		$columns['iwq_requests'] = 'iwq_requests';

		return $columns;
	}

	/**
	 * Ordena el listado por número de solicitudes.
	 *
	 * @param WP_Query $query Consulta principal del listado.
	 * @return void
	 */
	public function sort_by_requests( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || 'iwq_requests' !== $query->get( 'orderby' ) ) {
			return;
		}

		$query->set( 'meta_key', '_iwq_request_count' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$query->set( 'orderby', 'meta_value_num' );
	}

	/* ---------------------------------------------------------------------
	 * Acciones sobre el pedido
	 * ------------------------------------------------------------------ */

	/**
	 * Añade las acciones del plugin al desplegable del pedido.
	 *
	 * @param array<string,string> $actions Acciones disponibles.
	 * @return array<string,string>
	 */
	public function add_order_actions( $actions ) {
		global $theorder;

		if ( ! $theorder || ! iwq_is_quote( $theorder ) ) {
			return $actions;
		}

		$actions['iwq_send_quote'] = __( 'Enviar el presupuesto al cliente', 'imagina-woo-quotes' );

		if ( IWQ_PDF::is_available() ) {
			$actions['iwq_regenerate_pdf'] = __( 'Regenerar el PDF', 'imagina-woo-quotes' );
		}

		return $actions;
	}

	/**
	 * Envía el presupuesto desde el desplegable de acciones.
	 *
	 * @param WC_Order $order Pedido.
	 * @return void
	 */
	public function action_send_quote( $order ) {
		$quote = new IWQ_Quote( $order );

		if ( ! $quote->send() ) {
			$order->add_order_note( __( 'No se pudo enviar el presupuesto desde este estado.', 'imagina-woo-quotes' ) );
		}
	}

	/**
	 * Regenera el PDF descartando el cacheado.
	 *
	 * @param WC_Order $order Pedido.
	 * @return void
	 */
	public function action_regenerate_pdf( $order ) {
		IWQ_PDF::get_or_generate( $order->get_id(), true );
	}
}
