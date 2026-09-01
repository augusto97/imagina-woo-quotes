<?php
/**
 * Sección «Mis presupuestos» en Mi Cuenta.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_My_Account
 */
class IWQ_My_Account {

	const ENDPOINT = 'presupuestos';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_filter( 'query_vars', array( $this, 'add_query_var' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render' ) );

		// Los presupuestos no deben aparecer entre los pedidos normales.
		add_filter( 'woocommerce_my_account_my_orders_query', array( $this, 'exclude_quotes_from_orders' ) );

		// Vista del presupuesto dentro del detalle del pedido.
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'render_quote_actions' ) );

		add_action( 'init', array( $this, 'maybe_flush_rules' ) );
	}

	/**
	 * Registra el endpoint.
	 *
	 * @return void
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Reescribe las reglas la primera vez tras activar el plugin.
	 *
	 * @return void
	 */
	public function maybe_flush_rules() {
		if ( 'yes' !== get_option( 'iwq_flush_rewrite_rules' ) ) {
			return;
		}

		flush_rewrite_rules();
		delete_option( 'iwq_flush_rewrite_rules' );
	}

	/**
	 * Declara la variable de consulta del endpoint.
	 *
	 * @param string[] $vars Variables registradas.
	 * @return string[]
	 */
	public function add_query_var( $vars ) {
		$vars[] = self::ENDPOINT;

		return $vars;
	}

	/**
	 * Añade la entrada al menú de Mi Cuenta.
	 *
	 * @param array<string,string> $items Entradas del menú.
	 * @return array<string,string>
	 */
	public function add_menu_item( $items ) {
		$new = array();

		foreach ( $items as $key => $label ) {
			$new[ $key ] = $label;

			// Justo después de «Pedidos», que es donde el cliente lo busca.
			if ( 'orders' === $key ) {
				$new[ self::ENDPOINT ] = __( 'Mis presupuestos', 'imagina-woo-quotes' );
			}
		}

		if ( ! isset( $new[ self::ENDPOINT ] ) ) {
			$new[ self::ENDPOINT ] = __( 'Mis presupuestos', 'imagina-woo-quotes' );
		}

		return $new;
	}

	/**
	 * Pinta el listado de presupuestos del cliente.
	 *
	 * @return void
	 */
	public function render() {
		IWQ_Frontend::require_assets();

		$per_page = (int) apply_filters( 'iwq_my_account_per_page', 10 );
		$page     = max( 1, (int) get_query_var( 'paged' ) );

		$orders = wc_get_orders(
			array(
				'customer_id' => get_current_user_id(),
				'status'      => iwq_get_quote_statuses(),
				'limit'       => $per_page,
				'paged'       => $page,
				'paginate'    => true,
			)
		);

		iwq_get_template(
			'myaccount/quotes.php',
			array(
				'orders'       => $orders->orders,
				'total_pages'  => $orders->max_num_pages,
				'current_page' => $page,
			)
		);
	}

	/**
	 * Excluye los presupuestos del listado de pedidos.
	 *
	 * @param array $args Argumentos de la consulta.
	 * @return array
	 */
	public function exclude_quotes_from_orders( $args ) {
		$statuses = isset( $args['status'] ) ? (array) $args['status'] : array_keys( wc_get_order_statuses() );

		$args['status'] = array_diff(
			array_map(
				static function ( $status ) {
					return str_replace( 'wc-', '', $status );
				},
				$statuses
			),
			iwq_get_quote_statuses()
		);

		return $args;
	}

	/**
	 * Añade los botones de respuesta al detalle del pedido.
	 *
	 * @param WC_Order $order Pedido.
	 * @return void
	 */
	public function render_quote_actions( $order ) {
		if ( ! iwq_is_quote( $order ) ) {
			return;
		}

		$quote = iwq_get_quote( $order );

		if ( ! $quote ) {
			return;
		}

		IWQ_Frontend::require_assets();

		iwq_get_template(
			'myaccount/quote-actions.php',
			array(
				'order' => $order,
				'quote' => $quote,
			)
		);
	}
}
