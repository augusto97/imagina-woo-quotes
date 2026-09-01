<?php
/**
 * Bloques del front: contador y lista de presupuesto.
 *
 * Se renderizan en servidor reutilizando los shortcodes, de modo que hay una
 * sola implementación que mantener.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Blocks
 */
class IWQ_Blocks {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ), 20 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Registra los bloques.
	 *
	 * @return void
	 */
	public function register() {
		register_block_type(
			'imagina-quotes/quote-count',
			array(
				'api_version'     => 3,
				'render_callback' => array( $this, 'render_count' ),
				'attributes'      => array(
					'label'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'showIcon' => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);

		register_block_type(
			'imagina-quotes/quote-list',
			array(
				'api_version'     => 3,
				'render_callback' => array( $this, 'render_list' ),
			)
		);
	}

	/**
	 * Pinta el contador.
	 *
	 * @param array $attributes Atributos del bloque.
	 * @return string
	 */
	public function render_count( $attributes ) {
		$shortcodes = IWQ::instance()->get( 'shortcodes' );

		if ( ! $shortcodes ) {
			return '';
		}

		return $shortcodes->quote_count(
			array(
				'label' => ! empty( $attributes['label'] )
					? $attributes['label']
					: __( 'Presupuesto', 'imagina-woo-quotes' ),
				'icon'  => empty( $attributes['showIcon'] ) ? 'no' : 'yes',
			)
		);
	}

	/**
	 * Pinta la lista con el formulario.
	 *
	 * @return string
	 */
	public function render_list() {
		$shortcodes = IWQ::instance()->get( 'shortcodes' );

		return $shortcodes ? $shortcodes->quote_list() : '';
	}

	/**
	 * Carga el script del editor.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		wp_enqueue_script(
			'iwq-blocks',
			IWQ_URL . 'blocks/frontend-blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			IWQ_VERSION,
			true
		);

		wp_set_script_translations( 'iwq-blocks', 'imagina-woo-quotes' );
	}
}
