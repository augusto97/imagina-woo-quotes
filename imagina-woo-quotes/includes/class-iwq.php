<?php
/**
 * Contenedor principal del plugin.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ
 *
 * Singleton que instancia los módulos y expone las referencias compartidas.
 */
final class IWQ {

	/**
	 * Instancia única.
	 *
	 * @var IWQ|null
	 */
	private static $instance = null;

	/**
	 * Módulos instanciados, indexados por clave.
	 *
	 * @var array<string,object>
	 */
	private $modules = array();

	/**
	 * Devuelve la instancia única.
	 *
	 * @return IWQ
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor privado: arranca los módulos.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_modules();

		add_action( 'init', array( $this, 'load_textdomain' ) );

		/**
		 * Se dispara cuando el plugin terminó de cargar sus módulos.
		 *
		 * @param IWQ $plugin Instancia del plugin.
		 */
		do_action( 'iwq_loaded', $this );
	}

	/**
	 * Carga los archivos que no pasan por el autoloader.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		// Las funciones se cargan desde el archivo principal para que estén
		// disponibles durante la activación, cuando `plugins_loaded` de este
		// plugin todavía no se ha ejecutado.
		if ( is_readable( IWQ_DIR . 'vendor/autoload.php' ) ) {
			require_once IWQ_DIR . 'vendor/autoload.php';
		}
	}

	/**
	 * Instancia los módulos según el contexto.
	 *
	 * @return void
	 */
	private function init_modules() {
		// Siempre.
		$this->modules['statuses']   = new IWQ_Order_Statuses();
		$this->modules['uploads']    = new IWQ_Uploads();
		$this->modules['session']    = new IWQ_Session();
		$this->modules['exclusions'] = new IWQ_Exclusions();
		$this->modules['request']    = new IWQ_Request_Handler();
		$this->modules['emails']     = new IWQ_Emails();
		$this->modules['pdf_cpt']    = new IWQ_PDF_Template_CPT();
		$this->modules['pdf']        = new IWQ_PDF();
		$this->modules['cron']       = new IWQ_Cron();
		$this->modules['shortcodes'] = new IWQ_Shortcodes();
		$this->modules['my_account'] = new IWQ_My_Account();
		$this->modules['gateway']    = new IWQ_Gateway_Loader();
		$this->modules['blocks']     = new IWQ_Blocks();

		// Solo front.
		if ( ! is_admin() || wp_doing_ajax() ) {
			$this->modules['frontend'] = new IWQ_Frontend();
			$this->modules['assets']   = new IWQ_Assets();
		}

		// Solo admin.
		if ( is_admin() ) {
			$this->modules['admin']            = new IWQ_Admin();
			$this->modules['settings']         = new IWQ_Settings();
			$this->modules['form_admin']       = new IWQ_Form_Admin();
			$this->modules['order_metabox']    = new IWQ_Order_Metabox();
			$this->modules['exclusions_admin'] = new IWQ_Exclusions_Admin();
			$this->modules['preview']          = new IWQ_Preview();
		}
	}

	/**
	 * Devuelve un módulo por su clave.
	 *
	 * @param string $key Clave del módulo.
	 * @return object|null
	 */
	public function get( $key ) {
		return isset( $this->modules[ $key ] ) ? $this->modules[ $key ] : null;
	}

	/**
	 * Carga las traducciones.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'imagina-woo-quotes', false, dirname( IWQ_BASENAME ) . '/languages' );
	}

	/**
	 * Evita la clonación del singleton.
	 *
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'IWQ es un singleton y no debe clonarse.', 'imagina-woo-quotes' ), '1.0.0' );
	}

	/**
	 * Evita la deserialización del singleton.
	 *
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'IWQ es un singleton y no debe deserializarse.', 'imagina-woo-quotes' ), '1.0.0' );
	}
}
