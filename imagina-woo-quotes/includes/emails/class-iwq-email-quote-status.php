<?php
/**
 * Email: Respuesta del cliente.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'IWQ_Email_Base' ) ) {
	return;
}

/**
 * Class IWQ_Email_Quote_Status
 */
class IWQ_Email_Quote_Status extends IWQ_Email_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'iwq_quote_status';
		$this->title          = __( 'Respuesta del cliente', 'imagina-woo-quotes' );
		$this->description    = __( 'Avisa al administrador cuando el cliente acepta o rechaza un presupuesto.', 'imagina-woo-quotes' );
		$this->template_html  = 'emails/iwq-quote-status.php';
		$this->template_plain = 'emails/plain/iwq-quote-status.php';
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
		return true;
	}

	/**
	 * Asunto por defecto.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{site_title}] El presupuesto {order_number} ha sido respondido', 'imagina-woo-quotes' );
	}

	/**
	 * Encabezado por defecto.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Respuesta a un presupuesto', 'imagina-woo-quotes' );
	}
}
