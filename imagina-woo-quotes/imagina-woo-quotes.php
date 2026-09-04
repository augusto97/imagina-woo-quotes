<?php
/**
 * Plugin Name: Imagina Woo Quotes
 * Plugin URI:  https://github.com/augusto97/imagina-woo-quotes
 * Description: Permite a los clientes armar una lista de productos y solicitar un presupuesto. El presupuesto se gestiona como un pedido de WooCommerce, con PDF, emails y formulario configurable.
 * Version:     1.9.3
 * Author:      Imagina
 * Text Domain: imagina-woo-quotes
 * Domain Path: /languages
 * License:     GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 10.9
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

define( 'IWQ_VERSION', '1.9.3' );
define( 'IWQ_FILE', __FILE__ );
define( 'IWQ_DIR', plugin_dir_path( __FILE__ ) );
define( 'IWQ_URL', plugin_dir_url( __FILE__ ) );
define( 'IWQ_BASENAME', plugin_basename( __FILE__ ) );
define( 'IWQ_SLUG', 'imagina-woo-quotes' );
define( 'IWQ_TEMPLATE_PATH', IWQ_DIR . 'templates/' );

require_once IWQ_DIR . 'includes/class-iwq-autoloader.php';
require_once IWQ_DIR . 'includes/iwq-core-functions.php';
require_once IWQ_DIR . 'includes/iwq-form-functions.php';
require_once IWQ_DIR . 'includes/iwq-template-functions.php';

/**
 * Declara compatibilidad con HPOS (almacenamiento de pedidos en tablas propias)
 * y con los bloques de carrito/checkout.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			return;
		}
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', IWQ_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', IWQ_FILE, true );
	}
);

/**
 * Arranca el plugin una vez que sabemos que WooCommerce está presente.
 *
 * Se engancha en `plugins_loaded` con prioridad tardía para que WooCommerce
 * ya haya definido sus clases base (WC_Email, WC_Payment_Gateway, etc.).
 */
add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', 'iwq_missing_woocommerce_notice' );
			return;
		}

		IWQ::instance();
	},
	20
);

register_activation_hook( IWQ_FILE, array( 'IWQ_Install', 'activate' ) );
register_deactivation_hook( IWQ_FILE, array( 'IWQ_Install', 'deactivate' ) );
