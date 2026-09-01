<?php
/**
 * Email: Recordatorio de vencimiento (cliente).
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'IWQ_Email_Base' ) ) {
	return;
}

/**
 * Class IWQ_Email_Reminder
 */
class IWQ_Email_Reminder extends IWQ_Email_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'iwq_reminder';
		$this->title          = __( 'Recordatorio de vencimiento (cliente)', 'imagina-woo-quotes' );
		$this->description    = __( 'Recuerda al cliente que su presupuesto está a punto de vencer.', 'imagina-woo-quotes' );
		$this->template_html  = 'emails/iwq-reminder.php';
		$this->template_plain = 'emails/plain/iwq-reminder.php';
		$this->placeholders   = array(
			'{order_date}'    => '',
			'{order_number}'  => '',
			'{customer_name}' => '',
			'{expiry_date}'   => '',
		);

		parent::__construct();
	}

	/**
	 * Indica si el email va dirigido al administrador.
	 *
	 * @return bool
	 */
	protected function is_for_admin() {
		return false;
	}

	/**
	 * Asunto por defecto.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Tu presupuesto {order_number} vence el {expiry_date}', 'imagina-woo-quotes' );
	}

	/**
	 * Encabezado por defecto.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Tu presupuesto está a punto de vencer', 'imagina-woo-quotes' );
	}
}
