<?php
/**
 * Registra la pasarela «Solicitar presupuesto».
 *
 * Permite que el cliente convierta su carrito en una solicitud desde el
 * propio checkout, útil en tiendas que venden y presupuestan a la vez.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Gateway_Loader
 */
class IWQ_Gateway_Loader {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register' ) );
		add_action( 'woocommerce_blocks_loaded', array( $this, 'register_block_support' ) );
	}

	/**
	 * Añade la pasarela a la lista de WooCommerce.
	 *
	 * @param string[] $gateways Pasarelas registradas.
	 * @return string[]
	 */
	public function register( $gateways ) {
		$gateways[] = 'IWQ_Gateway';

		return $gateways;
	}

	/**
	 * Declara la pasarela ante el checkout de bloques.
	 *
	 * @return void
	 */
	public function register_block_support() {
		if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			return;
		}

		require_once IWQ_DIR . 'includes/class-iwq-gateway-blocks.php';

		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			static function ( $registry ) {
				$registry->register( new IWQ_Gateway_Blocks() );
			}
		);
	}
}
