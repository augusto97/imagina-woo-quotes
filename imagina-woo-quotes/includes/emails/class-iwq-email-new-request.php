<?php
/**
 * Email: Nueva solicitud de presupuesto.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'IWQ_Email_Base' ) ) {
	return;
}

/**
 * Class IWQ_Email_New_Request
 */
class IWQ_Email_New_Request extends IWQ_Email_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'iwq_new_request';
		$this->title          = __( 'Nueva solicitud de presupuesto', 'imagina-woo-quotes' );
		$this->description    = __( 'Avisa al administrador de que un cliente ha solicitado un presupuesto.', 'imagina-woo-quotes' );
		$this->template_html  = 'emails/iwq-new-request.php';
		$this->template_plain = 'emails/plain/iwq-new-request.php';
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
		return __( '[{site_title}] Nueva solicitud de presupuesto ({order_number}) de {customer_name}', 'imagina-woo-quotes' );
	}

	/**
	 * Encabezado por defecto.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Nueva solicitud de presupuesto', 'imagina-woo-quotes' );
	}
}
