<?php
/**
 * Tareas programadas: vencimiento, recordatorios y limpieza.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Cron
 */
class IWQ_Cron {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'schedule' ) );
		add_action( 'iwq_check_expiration', array( $this, 'expire_quotes' ) );
		add_action( 'iwq_send_reminders', array( $this, 'send_reminders' ) );
		add_action( 'iwq_clean_files', array( $this, 'clean_orphan_files' ) );
	}

	/**
	 * Programa las tareas si aún no lo están.
	 *
	 * @return void
	 */
	public function schedule() {
		$hooks = array(
			'iwq_check_expiration' => 'daily',
			'iwq_send_reminders'   => 'daily',
			'iwq_clean_files'      => 'weekly',
		);

		foreach ( $hooks as $hook => $recurrence ) {
			if ( ! wp_next_scheduled( $hook ) ) {
				// Arrancamos de madrugada para no competir con el tráfico.
				wp_schedule_event( strtotime( 'tomorrow 3:00' ), $recurrence, $hook );
			}
		}
	}

	/**
	 * Marca como vencidos los presupuestos que pasaron su fecha.
	 *
	 * @return void
	 */
	public function expire_quotes() {
		if ( ! iwq_option_enabled( 'auto_expire', true ) ) {
			return;
		}

		$orders = wc_get_orders(
			array(
				'status'     => array( 'iwq-pending' ),
				'limit'      => 100,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => IWQ_Quote::META_EXPIRY,
						'value'   => time(),
						'compare' => '<',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		foreach ( $orders as $order ) {
			$quote = new IWQ_Quote( $order );

			// El objeto vuelve a comprobar la fecha: entre la consulta y este
			// punto el administrador puede haberla ampliado.
			if ( $quote->is_expired() ) {
				$quote->expire();
			}
		}
	}

	/**
	 * Avisa a los clientes cuyo presupuesto está a punto de vencer.
	 *
	 * @return void
	 */
	public function send_reminders() {
		if ( ! iwq_option_enabled( 'reminders_enabled', true ) ) {
			return;
		}

		$days   = max( 1, (int) iwq_get_option( 'reminder_days_before', 2 ) );
		$window = time() + ( $days * DAY_IN_SECONDS );

		$orders = wc_get_orders(
			array(
				'status'     => array( 'iwq-pending' ),
				'limit'      => 100,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => IWQ_Quote::META_EXPIRY,
						'value'   => array( time(), $window ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		$mailer = WC()->mailer()->get_emails();

		foreach ( $orders as $order ) {
			$quote = new IWQ_Quote( $order );

			if ( $quote->reminder_was_sent( 'expiry' ) ) {
				continue;
			}

			if ( isset( $mailer['IWQ_Email_Reminder'] ) ) {
				$mailer['IWQ_Email_Reminder']->trigger( $order->get_id() );
				$quote->mark_reminder_sent( 'expiry' );
			}
		}
	}

	/**
	 * Borra los PDF de presupuestos que ya no existen.
	 *
	 * Sin esta limpieza el directorio crece indefinidamente con documentos
	 * de pedidos borrados.
	 *
	 * @return void
	 */
	public function clean_orphan_files() {
		$dir = IWQ_PDF::get_dir();

		if ( ! is_dir( $dir ) ) {
			return;
		}

		$files = glob( trailingslashit( $dir ) . '*.pdf' );

		if ( ! $files ) {
			return;
		}

		$max_age = (int) apply_filters( 'iwq_pdf_max_age', 90 * DAY_IN_SECONDS );

		foreach ( $files as $file ) {
			if ( filemtime( $file ) < time() - $max_age ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				@unlink( $file );
			}
		}
	}
}
