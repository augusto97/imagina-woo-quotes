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
	 * Carga los assets del admin solo en nuestras pantallas.
	 *
	 * @param string $hook Identificador de la pantalla.
	 * @return void
	 */
	public function enqueue( $hook ) {
		$screen    = get_current_screen();
		$is_ours   = false !== strpos( $hook, 'iwq-settings' );
		$is_order  = $screen && in_array( $screen->id, array( 'shop_order', 'woocommerce_page_wc-orders' ), true );

		if ( ! $is_ours && ! $is_order ) {
			return;
		}

		wp_enqueue_style( 'iwq-admin', IWQ_URL . 'assets/css/admin.css', array(), IWQ_VERSION );

		if ( $is_ours ) {
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
					),
				)
			);

			wp_enqueue_media();
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_style( 'woocommerce_admin_styles' );
		}
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
