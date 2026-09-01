<?php
/**
 * Email: Contraoferta del cliente.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'IWQ_Email_Base' ) ) {
	return;
}

/**
 * Class IWQ_Email_Counter_Offer
 */
class IWQ_Email_Counter_Offer extends IWQ_Email_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'iwq_counter_offer';
		$this->title          = __( 'Contraoferta del cliente', 'imagina-woo-quotes' );
		$this->description    = __( 'Avisa al administrador cuando un cliente propone un precio distinto.', 'imagina-woo-quotes' );
		$this->template_html  = 'emails/iwq-counter-offer.php';
		$this->template_plain = 'emails/plain/iwq-counter-offer.php';
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
		return __( '[{site_title}] Contraoferta en el presupuesto {order_number}', 'imagina-woo-quotes' );
	}

	/**
	 * Encabezado por defecto.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Nueva contraoferta', 'imagina-woo-quotes' );
	}
}
