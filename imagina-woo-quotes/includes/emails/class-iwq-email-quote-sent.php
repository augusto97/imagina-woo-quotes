<?php
/**
 * Email: Presupuesto enviado (cliente).
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'IWQ_Email_Base' ) ) {
	return;
}

/**
 * Class IWQ_Email_Quote_Sent
 */
class IWQ_Email_Quote_Sent extends IWQ_Email_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'iwq_quote_sent';
		$this->title          = __( 'Presupuesto enviado (cliente)', 'imagina-woo-quotes' );
		$this->description    = __( 'Envía al cliente el presupuesto ya valorado, con los enlaces para aceptarlo o rechazarlo.', 'imagina-woo-quotes' );
		$this->template_html  = 'emails/iwq-quote-sent.php';
		$this->template_plain = 'emails/plain/iwq-quote-sent.php';
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
		return __( 'Tu presupuesto {order_number} ya está listo', 'imagina-woo-quotes' );
	}

	/**
	 * Encabezado por defecto.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Tu presupuesto', 'imagina-woo-quotes' );
	}
}
