<?php
/**
 * Estados de pedido que representan las fases de un presupuesto.
 *
 * Un presupuesto es un WC_Order normal: así heredamos HPOS, informes,
 * reembolsos, notas y toda la infraestructura de WooCommerce sin
 * reimplementar nada.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Order_Statuses
 */
class IWQ_Order_Statuses {

	/**
	 * Colores de cada estado, usados en el listado de pedidos del admin.
	 *
	 * @var array<string,array{bg:string,fg:string}>
	 */
	private static $colors = array(
		'iwq-new'      => array( 'bg' => '#e0e7ff', 'fg' => '#3730a3' ),
		'iwq-pending'  => array( 'bg' => '#fef3c7', 'fg' => '#92400e' ),
		'iwq-accepted' => array( 'bg' => '#d1fae5', 'fg' => '#065f46' ),
		'iwq-rejected' => array( 'bg' => '#fee2e2', 'fg' => '#991b1b' ),
		'iwq-expired'  => array( 'bg' => '#e5e7eb', 'fg' => '#4b5563' ),
	);

	/**
	 * Constructor: engancha el registro de estados.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'wc_order_statuses', array( __CLASS__, 'add_to_order_statuses' ) );
		add_filter( 'woocommerce_reports_order_statuses', array( __CLASS__, 'exclude_from_reports' ) );
		add_filter( 'wc_order_is_editable', array( __CLASS__, 'make_editable' ), 10, 2 );
		add_filter( 'woocommerce_valid_order_statuses_for_payment', array( __CLASS__, 'valid_for_payment' ), 10, 2 );
		add_filter( 'woocommerce_valid_order_statuses_for_cancel', array( __CLASS__, 'valid_for_cancel' ), 10, 2 );
		add_action( 'admin_head', array( __CLASS__, 'print_status_styles' ) );
	}

	/**
	 * Devuelve la definición de los estados.
	 *
	 * Se construye en tiempo de ejecución porque las etiquetas necesitan
	 * que el textdomain ya esté cargado.
	 *
	 * @return array<string,string> Mapa de `wc-slug` a etiqueta.
	 */
	public static function get_statuses() {
		return array(
			'wc-iwq-new'      => _x( 'Presupuesto solicitado', 'Estado de pedido', 'imagina-woo-quotes' ),
			'wc-iwq-pending'  => _x( 'Presupuesto enviado', 'Estado de pedido', 'imagina-woo-quotes' ),
			'wc-iwq-accepted' => _x( 'Presupuesto aceptado', 'Estado de pedido', 'imagina-woo-quotes' ),
			'wc-iwq-rejected' => _x( 'Presupuesto rechazado', 'Estado de pedido', 'imagina-woo-quotes' ),
			'wc-iwq-expired'  => _x( 'Presupuesto vencido', 'Estado de pedido', 'imagina-woo-quotes' ),
		);
	}

	/**
	 * Registra los estados como post status de WordPress.
	 *
	 * @return void
	 */
	public static function register() {
		$counts = array(
			'wc-iwq-new'      => _n_noop( 'Presupuesto solicitado <span class="count">(%s)</span>', 'Presupuestos solicitados <span class="count">(%s)</span>', 'imagina-woo-quotes' ),
			'wc-iwq-pending'  => _n_noop( 'Presupuesto enviado <span class="count">(%s)</span>', 'Presupuestos enviados <span class="count">(%s)</span>', 'imagina-woo-quotes' ),
			'wc-iwq-accepted' => _n_noop( 'Presupuesto aceptado <span class="count">(%s)</span>', 'Presupuestos aceptados <span class="count">(%s)</span>', 'imagina-woo-quotes' ),
			'wc-iwq-rejected' => _n_noop( 'Presupuesto rechazado <span class="count">(%s)</span>', 'Presupuestos rechazados <span class="count">(%s)</span>', 'imagina-woo-quotes' ),
			'wc-iwq-expired'  => _n_noop( 'Presupuesto vencido <span class="count">(%s)</span>', 'Presupuestos vencidos <span class="count">(%s)</span>', 'imagina-woo-quotes' ),
		);

		foreach ( self::get_statuses() as $status => $label ) {
			register_post_status(
				$status,
				array(
					'label'                     => $label,
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => $counts[ $status ],
				)
			);
		}
	}

	/**
	 * Añade los estados a la lista que WooCommerce muestra en el admin.
	 *
	 * @param array<string,string> $statuses Estados existentes.
	 * @return array<string,string>
	 */
	public static function add_to_order_statuses( $statuses ) {
		$new = array();

		// Los insertamos justo después de `pending` para que queden agrupados
		// al principio del desplegable, antes del flujo de venta normal.
		foreach ( $statuses as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'wc-pending' === $key ) {
				$new = array_merge( $new, self::get_statuses() );
			}
		}

		// Si no existía `wc-pending`, los añadimos al final.
		if ( ! isset( $new['wc-iwq-new'] ) ) {
			$new = array_merge( $new, self::get_statuses() );
		}

		return $new;
	}

	/**
	 * Excluye los presupuestos de los informes de ventas.
	 *
	 * Un presupuesto sin aceptar no es una venta y contarlo distorsionaría
	 * las métricas de la tienda.
	 *
	 * @param string[] $statuses Estados incluidos en los informes.
	 * @return string[]
	 */
	public static function exclude_from_reports( $statuses ) {
		return array_diff( $statuses, iwq_get_quote_statuses() );
	}

	/**
	 * Permite editar líneas y precios mientras el presupuesto se negocia.
	 *
	 * @param bool     $is_editable Valor actual.
	 * @param WC_Order $order       Pedido.
	 * @return bool
	 */
	public static function make_editable( $is_editable, $order ) {
		if ( $order instanceof WC_Order && in_array( $order->get_status(), array( 'iwq-new', 'iwq-pending', 'iwq-expired' ), true ) ) {
			return true;
		}

		return $is_editable;
	}

	/**
	 * Un presupuesto aceptado se puede pagar.
	 *
	 * @param string[] $statuses Estados válidos para pago.
	 * @param WC_Order $order    Pedido.
	 * @return string[]
	 */
	public static function valid_for_payment( $statuses, $order ) {
		$statuses[] = 'iwq-accepted';

		return $statuses;
	}

	/**
	 * El cliente puede cancelar un presupuesto que aún no aceptó.
	 *
	 * @param string[] $statuses Estados válidos para cancelar.
	 * @param WC_Order $order    Pedido.
	 * @return string[]
	 */
	public static function valid_for_cancel( $statuses, $order ) {
		return array_merge( $statuses, array( 'iwq-new', 'iwq-pending' ) );
	}

	/**
	 * Colorea las etiquetas de estado en el listado de pedidos.
	 *
	 * Son ~15 líneas de CSS; imprimirlas en línea evita una petición HTTP
	 * extra en el admin.
	 *
	 * @return void
	 */
	public static function print_status_styles() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! in_array( $screen->id, array( 'edit-shop_order', 'woocommerce_page_wc-orders' ), true ) ) {
			return;
		}

		echo '<style id="iwq-status-colors">';

		foreach ( self::$colors as $status => $color ) {
			printf(
				'.order-status.status-%1$s{background:%2$s;color:%3$s}',
				esc_attr( $status ),
				esc_attr( $color['bg'] ),
				esc_attr( $color['fg'] )
			);
		}

		echo '</style>';
	}
}
