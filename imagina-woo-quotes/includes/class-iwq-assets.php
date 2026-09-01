<?php
/**
 * Carga de estilos y scripts del front.
 *
 * La regla es simple: nada se encola en páginas que no lo necesitan. Un blog
 * o una página de contacto no deben pagar el peso de este plugin.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Assets
 */
class IWQ_Assets {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_recaptcha' ), 20 );
	}

	/**
	 * Encola los assets si la página los va a necesitar.
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( ! $this->page_needs_assets() ) {
			return;
		}

		wp_enqueue_style(
			'iwq-frontend',
			IWQ_URL . 'assets/css/frontend.css',
			array(),
			IWQ_VERSION
		);

		wp_enqueue_script(
			'iwq-frontend',
			IWQ_URL . 'assets/js/frontend.js',
			array(),
			IWQ_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_localize_script( 'iwq-frontend', 'iwqData', $this->get_script_data() );
	}

	/**
	 * Datos que el script necesita del servidor.
	 *
	 * @return array<string,mixed>
	 */
	private function get_script_data() {
		return array(
			'ajaxUrl'   => WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'nonce'     => wp_create_nonce( 'iwq_frontend' ),
			'quoteUrl'  => $this->get_quote_page_url(),
			'openAfterAdd' => iwq_option_enabled( 'open_drawer_after_add', true ),
			'redirect'  => iwq_option_enabled( 'redirect_after_add' ),
			'i18n'      => array(
				'added'     => iwq_get_option( 'button_label_added', __( 'Ya está en tu presupuesto', 'imagina-woo-quotes' ) ),
				'add'       => iwq_get_option( 'button_label', __( 'Solicitar presupuesto', 'imagina-woo-quotes' ) ),
				'adding'    => __( 'Añadiendo…', 'imagina-woo-quotes' ),
				'error'     => __( 'Algo ha fallado. Inténtalo de nuevo.', 'imagina-woo-quotes' ),
				'emptyList' => __( 'Tu lista de presupuesto está vacía.', 'imagina-woo-quotes' ),
				'confirmClear' => __( '¿Seguro que quieres vaciar la lista?', 'imagina-woo-quotes' ),
				'itemAdded' => __( 'Producto añadido a tu lista de presupuesto.', 'imagina-woo-quotes' ),
				'itemRemoved' => __( 'Producto quitado de tu lista.', 'imagina-woo-quotes' ),
				'closeDrawer' => __( 'Cerrar el panel', 'imagina-woo-quotes' ),
			),
		);
	}

	/**
	 * URL de la página de la lista de presupuesto.
	 *
	 * @return string
	 */
	private function get_quote_page_url() {
		$page_id = (int) iwq_get_option( 'quote_page_id' );

		return $page_id ? get_permalink( $page_id ) : '';
	}

	/**
	 * Decide si la página actual puede necesitar los assets.
	 *
	 * Se resuelve antes de renderizar, así que es una predicción: preferimos
	 * cubrir de más en las páginas de tienda a arriesgar un parpadeo de
	 * estilos sin aplicar.
	 *
	 * @return bool
	 */
	private function page_needs_assets() {
		if ( ! iwq_option_enabled( 'enabled', true ) ) {
			return false;
		}

		if ( IWQ_Frontend::needs_assets() || IWQ_Frontend::is_quote_page() ) {
			return true;
		}

		$is_woo_page = is_woocommerce() || is_cart() || is_checkout() || is_account_page();

		/**
		 * Filtra si la página actual carga los assets del plugin.
		 *
		 * Útil para una portada que muestra productos con el botón.
		 *
		 * @param bool $is_woo_page Decisión calculada.
		 */
		return (bool) apply_filters( 'iwq_enqueue_assets', $is_woo_page );
	}

	/**
	 * Encola el script de reCAPTCHA solo en la página del formulario.
	 *
	 * @return void
	 */
	public function enqueue_recaptcha() {
		if ( ! IWQ_Recaptcha::is_enabled() || ! IWQ_Frontend::is_quote_page() ) {
			return;
		}

		wp_enqueue_script(
			'iwq-recaptcha',
			IWQ_Recaptcha::get_script_url(),
			array(),
			null, // La URL de Google ya lleva su propio versionado.
			true
		);
	}
}
