<?php
/**
 * Autoloader PSR-0-ish adaptado a la convención de nombres de WordPress.
 *
 * Traduce `IWQ_Email_Quote_Sent` a `includes/emails/class-iwq-email-quote-sent.php`
 * probando los subdirectorios conocidos.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Autoloader
 */
class IWQ_Autoloader {

	/**
	 * Subdirectorios de includes/ donde buscar clases, en orden de preferencia.
	 *
	 * @var string[]
	 */
	private static $paths = array( '', 'emails/', 'forms/', 'pdf/', 'admin/' );

	/**
	 * Registra el autoloader.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Carga la clase pedida si nos pertenece.
	 *
	 * @param string $class_name Nombre de la clase solicitada.
	 * @return void
	 */
	public static function autoload( $class_name ) {
		if ( 0 !== strpos( $class_name, 'IWQ' ) ) {
			return;
		}

		$file = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

		foreach ( self::$paths as $path ) {
			$full = IWQ_DIR . 'includes/' . $path . $file;

			if ( is_readable( $full ) ) {
				require_once $full;
				return;
			}
		}
	}
}

IWQ_Autoloader::register();
