<?php
/**
 * Email: Solicitud recibida (cliente).
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'IWQ_Email_Base' ) ) {
	return;
}

/**
 * Class IWQ_Email_Request_Received
 */
class IWQ_Email_Request_Received extends IWQ_Email_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'iwq_request_received';
		$this->title          = __( 'Solicitud recibida (cliente)', 'imagina-woo-quotes' );
		$this->description    = __( 'Confirma al cliente que hemos recibido su solicitud.', 'imagina-woo-quotes' );
		$this->template_html  = 'emails/iwq-request-received.php';
		$this->template_plain = 'emails/plain/iwq-request-received.php';
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
		return __( 'Hemos recibido tu solicitud de presupuesto ({order_number})', 'imagina-woo-quotes' );
	}

	/**
	 * Encabezado por defecto.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Gracias por tu solicitud', 'imagina-woo-quotes' );
	}
}
