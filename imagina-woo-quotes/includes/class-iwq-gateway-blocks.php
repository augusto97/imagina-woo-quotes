<?php
/**
 * Soporte de la pasarela en el checkout de bloques.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Class IWQ_Gateway_Blocks
 */
class IWQ_Gateway_Blocks extends AbstractPaymentMethodType {

	/**
	 * Identificador de la pasarela.
	 *
	 * @var string
	 */
	protected $name = 'iwq_quote';

	/**
	 * Carga los ajustes guardados.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_iwq_quote_settings', array() );
	}

	/**
	 * Indica si la pasarela está activa.
	 *
	 * @return bool
	 */
	public function is_active() {
		return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
	}

	/**
	 * Registra el script del método de pago.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			'iwq-gateway-blocks',
			IWQ_URL . 'blocks/checkout-gateway.js',
			array( 'wc-blocks-registry', 'wp-element', 'wp-html-entities', 'wp-i18n' ),
			IWQ_VERSION,
			true
		);

		wp_set_script_translations( 'iwq-gateway-blocks', 'imagina-woo-quotes' );

		return array( 'iwq-gateway-blocks' );
	}

	/**
	 * Datos que el script necesita.
	 *
	 * @return array<string,mixed>
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Solicitar presupuesto', 'imagina-woo-quotes' ),
			'description' => isset( $this->settings['description'] ) ? $this->settings['description'] : '',
			'supports'    => array( 'products' ),
		);
	}
}
