<?php
/**
 * Tipo de contenido «plantilla de PDF», editable con el editor de bloques.
 *
 * Guardar las plantillas como posts permite que el comerciante las diseñe
 * visualmente, tenga revisiones y pueda duplicarlas, sin que nosotros
 * mantengamos un constructor propio.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_PDF_Template_CPT
 */
class IWQ_PDF_Template_CPT {

	const POST_TYPE = 'iwq_pdf_template';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_blocks' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_create_default' ) );
		add_filter( 'allowed_block_types_all', array( __CLASS__, 'filter_allowed_blocks' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );
	}

	/**
	 * Registra el tipo de contenido.
	 *
	 * @return void
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Plantillas de PDF', 'imagina-woo-quotes' ),
					'singular_name' => __( 'Plantilla de PDF', 'imagina-woo-quotes' ),
					'add_new_item'  => __( 'Añadir plantilla', 'imagina-woo-quotes' ),
					'edit_item'     => __( 'Editar plantilla', 'imagina-woo-quotes' ),
					'new_item'      => __( 'Nueva plantilla', 'imagina-woo-quotes' ),
					'search_items'  => __( 'Buscar plantillas', 'imagina-woo-quotes' ),
					'not_found'     => __( 'No hay plantillas todavía.', 'imagina-woo-quotes' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'woocommerce',
				'show_in_rest'    => true,
				'supports'        => array( 'title', 'editor', 'revisions' ),
				'capability_type' => 'iwq_pdf_template',
				'map_meta_cap'    => true,
				'rewrite'         => false,
				'query_var'       => false,
			)
		);
	}

	/**
	 * Registra los bloques propios de las plantillas.
	 *
	 * Cada uno se pinta en PHP en el momento de generar el PDF, así que el
	 * editor guarda solo el marcador y no HTML congelado.
	 *
	 * @return void
	 */
	public static function register_blocks() {
		$blocks = array(
			'quote-table'    => array( 'IWQ_PDF_Blocks', 'render_quote_table' ),
			'quote-totals'   => array( 'IWQ_PDF_Blocks', 'render_quote_totals' ),
			'customer-info'  => array( 'IWQ_PDF_Blocks', 'render_customer_info' ),
			'quote-meta'     => array( 'IWQ_PDF_Blocks', 'render_quote_meta' ),
			'form-data'      => array( 'IWQ_PDF_Blocks', 'render_form_data' ),
			'quote-actions'  => array( 'IWQ_PDF_Blocks', 'render_quote_actions' ),
			'store-logo'     => array( 'IWQ_PDF_Blocks', 'render_store_logo' ),
		);

		foreach ( $blocks as $name => $callback ) {
			register_block_type(
				'imagina-quotes/' . $name,
				array(
					'api_version'     => 3,
					'render_callback' => $callback,
					'attributes'      => array(
						'showImages' => array(
							'type'    => 'boolean',
							'default' => true,
						),
						'showSku'    => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
				)
			);
		}
	}

	/**
	 * Limita los bloques disponibles al editar una plantilla.
	 *
	 * Un PDF no admite vídeos ni bloques interactivos: ofrecer solo lo que
	 * funciona evita que el comerciante diseñe algo que luego no se imprime.
	 *
	 * @param bool|string[]           $allowed Bloques permitidos.
	 * @param WP_Block_Editor_Context $context Contexto del editor.
	 * @return bool|string[]
	 */
	public static function filter_allowed_blocks( $allowed, $context ) {
		if ( empty( $context->post ) || self::POST_TYPE !== $context->post->post_type ) {
			return $allowed;
		}

		return array(
			'core/paragraph',
			'core/heading',
			'core/list',
			'core/list-item',
			'core/image',
			'core/table',
			'core/separator',
			'core/spacer',
			'core/columns',
			'core/column',
			'core/group',
			'imagina-quotes/quote-table',
			'imagina-quotes/quote-totals',
			'imagina-quotes/customer-info',
			'imagina-quotes/quote-meta',
			'imagina-quotes/form-data',
			'imagina-quotes/quote-actions',
			'imagina-quotes/store-logo',
		);
	}

	/**
	 * Carga el script que define los bloques en el editor.
	 *
	 * Se escribe con `wp.element.createElement` en vez de JSX para que el
	 * plugin no necesite un paso de compilación ni dependencias de npm.
	 *
	 * @return void
	 */
	public static function enqueue_editor_assets() {
		$screen = get_current_screen();

		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'iwq-pdf-blocks',
			IWQ_URL . 'blocks/pdf-blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			IWQ_VERSION,
			true
		);

		wp_enqueue_style(
			'iwq-pdf-blocks',
			IWQ_URL . 'blocks/pdf-blocks.css',
			array(),
			IWQ_VERSION
		);

		wp_set_script_translations( 'iwq-pdf-blocks', 'imagina-woo-quotes' );
	}

	/**
	 * Crea la plantilla por defecto la primera vez.
	 *
	 * @return void
	 */
	public static function maybe_create_default() {
		if ( 'yes' === get_option( 'iwq_default_template_created' ) ) {
			return;
		}

		$existing = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);

		if ( $existing ) {
			update_option( 'iwq_default_template_created', 'yes' );
			return;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_title'   => __( 'Plantilla de presupuesto', 'imagina-woo-quotes' ),
				'post_status'  => 'publish',
				'post_content' => self::get_default_content(),
			)
		);

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			iwq_update_option( 'pdf_template_id', $post_id );
		}

		update_option( 'iwq_default_template_created', 'yes' );
	}

	/**
	 * Contenido de bloques de la plantilla por defecto.
	 *
	 * @return string
	 */
	private static function get_default_content() {
		$blocks = array(
			'<!-- wp:imagina-quotes/store-logo /-->',
			'<!-- wp:heading {"level":1} --><h1>' . esc_html__( 'Presupuesto {order_number}', 'imagina-woo-quotes' ) . '</h1><!-- /wp:heading -->',
			'<!-- wp:imagina-quotes/quote-meta /-->',
			'<!-- wp:imagina-quotes/customer-info /-->',
			'<!-- wp:imagina-quotes/quote-table /-->',
			'<!-- wp:imagina-quotes/quote-totals /-->',
			'<!-- wp:imagina-quotes/form-data /-->',
			'<!-- wp:imagina-quotes/quote-actions /-->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'Presupuesto válido hasta el {expiry_date}. Gracias por confiar en {site_title}.', 'imagina-woo-quotes' ) . '</p><!-- /wp:paragraph -->',
		);

		return implode( "\n\n", $blocks );
	}

	/**
	 * Devuelve las plantillas publicadas, para los desplegables de ajustes.
	 *
	 * @return array<int,string>
	 */
	public static function get_choices() {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => 100,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$choices = array();

		foreach ( $posts as $post ) {
			$choices[ $post->ID ] = $post->post_title;
		}

		return $choices;
	}
}
