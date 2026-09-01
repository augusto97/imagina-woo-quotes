<?php
/**
 * Estadísticas de presupuestos.
 *
 * Los gráficos se dibujan con SVG generado en PHP en lugar de cargar una
 * librería: son barras y porcentajes, y Chart.js pesa más de 200 KB para
 * esto.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Statistics
 */
class IWQ_Statistics {

	/**
	 * Calcula las métricas del periodo indicado.
	 *
	 * @param int $days Número de días hacia atrás.
	 * @return array<string,mixed>
	 */
	public static function get_data( $days = 30 ) {
		$cache_key = 'iwq_stats_' . $days;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$after  = gmdate( 'Y-m-d', time() - ( $days * DAY_IN_SECONDS ) );
		$counts = array();
		$total  = 0;

		foreach ( iwq_get_quote_statuses() as $status ) {
			$orders = wc_get_orders(
				array(
					'status'       => $status,
					'date_created' => '>=' . $after,
					'limit'        => -1,
					'return'       => 'ids',
				)
			);

			$counts[ $status ] = count( $orders );
			$total            += $counts[ $status ];
		}

		$responded = $counts['iwq-accepted'] + $counts['iwq-rejected'];

		$data = array(
			'total'     => $total,
			'counts'    => $counts,
			'accepted'  => $counts['iwq-accepted'],
			// Tasa de aceptación sobre los que el cliente respondió: incluir
			// los que siguen pendientes falsearía el dato a la baja.
			'accept_rate' => $responded ? round( $counts['iwq-accepted'] / $responded * 100, 1 ) : 0,
			'response_rate' => $total ? round( $responded / $total * 100, 1 ) : 0,
			'value'     => self::get_accepted_value( $after ),
			'top'       => self::get_top_products(),
		);

		set_transient( $cache_key, $data, HOUR_IN_SECONDS );

		return $data;
	}

	/**
	 * Suma el importe de los presupuestos aceptados del periodo.
	 *
	 * @param string $after Fecha de inicio.
	 * @return float
	 */
	private static function get_accepted_value( $after ) {
		$orders = wc_get_orders(
			array(
				'status'       => 'iwq-accepted',
				'date_created' => '>=' . $after,
				'limit'        => -1,
			)
		);

		$total = 0;

		foreach ( $orders as $order ) {
			$total += (float) $order->get_total();
		}

		return $total;
	}

	/**
	 * Productos más solicitados, según el contador acumulado.
	 *
	 * @param int $limit Número de productos.
	 * @return array<int,array{name:string,count:int,id:int}>
	 */
	public static function get_top_products( $limit = 10 ) {
		$products = get_posts(
			array(
				'post_type'      => 'product',
				'posts_per_page' => $limit,
				'meta_key'       => '_iwq_request_count', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_iwq_request_count',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		$top = array();

		foreach ( $products as $product ) {
			$top[] = array(
				'id'    => $product->ID,
				'name'  => $product->post_title,
				'count' => (int) get_post_meta( $product->ID, '_iwq_request_count', true ),
			);
		}

		return $top;
	}

	/**
	 * Pinta la pestaña de estadísticas.
	 *
	 * @return void
	 */
	public static function render() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo elige el periodo mostrado.
		$days = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30;
		$days = in_array( $days, array( 7, 30, 90, 365 ), true ) ? $days : 30;

		iwq_get_template(
			'admin/statistics.php',
			array(
				'data' => self::get_data( $days ),
				'days' => $days,
			)
		);
	}
}
