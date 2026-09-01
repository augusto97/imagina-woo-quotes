<?php
/**
 * Página de ajustes del plugin.
 *
 * Se apoya en la API de ajustes de WordPress y en los estilos nativos del
 * admin, sin ningún framework propio: es la diferencia entre pesar unos
 * kilobytes y arrastrar un framework de varios megas en cada pantalla.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Settings
 */
class IWQ_Settings {

	const OPTION_GROUP = 'iwq_settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Devuelve las pestañas de la página.
	 *
	 * @return array<string,string>
	 */
	public static function get_tabs() {
		return array(
			'general'     => __( 'General', 'imagina-woo-quotes' ),
			'display'     => __( 'Botones y catálogo', 'imagina-woo-quotes' ),
			'quote'       => __( 'Presupuestos', 'imagina-woo-quotes' ),
			'form'        => __( 'Formulario', 'imagina-woo-quotes' ),
			'pdf'         => __( 'PDF', 'imagina-woo-quotes' ),
			'rules'       => __( 'Reglas', 'imagina-woo-quotes' ),
			'stats'       => __( 'Estadísticas', 'imagina-woo-quotes' ),
		);
	}

	/**
	 * Definición de todos los campos, agrupados por pestaña y sección.
	 *
	 * @param string $tab Pestaña solicitada.
	 * @return array<string,array{title:string,fields:array}>
	 */
	public static function get_sections( $tab ) {
		$sections = array(
			'general' => array(
				'main' => array(
					'title'  => __( 'Funcionamiento', 'imagina-woo-quotes' ),
					'fields' => array(
						'enabled'      => array(
							'label' => __( 'Activar los presupuestos', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
							'desc'  => __( 'Desactívalo para ocultar el sistema sin desinstalar el plugin.', 'imagina-woo-quotes' ),
						),
						'quote_page_id' => array(
							'label' => __( 'Página de la solicitud', 'imagina-woo-quotes' ),
							'type'  => 'page',
							'desc'  => __( 'Debe contener el bloque «Lista de presupuesto» o el shortcode [iwq_quote_list].', 'imagina-woo-quotes' ),
						),
						'thankyou_page_id' => array(
							'label' => __( 'Página de agradecimiento', 'imagina-woo-quotes' ),
							'type'  => 'page',
							'desc'  => __( 'Opcional. Si la dejas vacía, el cliente ve el mensaje sin salir de la página.', 'imagina-woo-quotes' ),
						),
						'allow_guests' => array(
							'label' => __( 'Permitir a visitantes sin cuenta', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'allowed_roles' => array(
							'label' => __( 'Limitar a estos roles', 'imagina-woo-quotes' ),
							'type'  => 'roles',
							'desc'  => __( 'Déjalo vacío para permitirlo a todo el mundo.', 'imagina-woo-quotes' ),
						),
						'rate_limit'   => array(
							'label' => __( 'Máximo de solicitudes por hora e IP', 'imagina-woo-quotes' ),
							'type'  => 'number',
							'desc'  => __( 'Protege el formulario del envío masivo. Cero lo desactiva.', 'imagina-woo-quotes' ),
						),
					),
				),
			),

			'display' => array(
				'button' => array(
					'title'  => __( 'Botón', 'imagina-woo-quotes' ),
					'fields' => array(
						'button_label'        => array(
							'label' => __( 'Texto del botón', 'imagina-woo-quotes' ),
							'type'  => 'text',
						),
						'button_label_added'  => array(
							'label' => __( 'Texto cuando ya está añadido', 'imagina-woo-quotes' ),
							'type'  => 'text',
						),
						'button_style'        => array(
							'label'   => __( 'Estilo', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'solid'   => __( 'Relleno', 'imagina-woo-quotes' ),
								'outline' => __( 'Contorno', 'imagina-woo-quotes' ),
							),
						),
						'show_on_product'     => array(
							'label' => __( 'Mostrar en la ficha de producto', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'button_position_single' => array(
							'label'   => __( 'Posición en la ficha', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'after_add_to_cart'  => __( 'Debajo del botón de compra', 'imagina-woo-quotes' ),
								'before_add_to_cart' => __( 'Encima del botón de compra', 'imagina-woo-quotes' ),
							),
						),
						'show_on_shop'        => array(
							'label' => __( 'Mostrar en el catálogo', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'show_on_cart'        => array(
							'label' => __( 'Mostrar en el carrito', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
							'desc'  => __( 'Añade un botón para pasar el carrito entero a presupuesto.', 'imagina-woo-quotes' ),
						),
						'open_drawer_after_add' => array(
							'label' => __( 'Abrir el panel al añadir', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'redirect_after_add'  => array(
							'label' => __( 'Ir a la página de solicitud al añadir', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
					),
				),
				'catalog' => array(
					'title'  => __( 'Precios y compra', 'imagina-woo-quotes' ),
					'fields' => array(
						'hide_price'       => array(
							'label' => __( 'Ocultar precios', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'hide_price_text'  => array(
							'label' => __( 'Texto en lugar del precio', 'imagina-woo-quotes' ),
							'type'  => 'text',
						),
						'hide_price_roles' => array(
							'label' => __( 'Ocultar el precio solo a estos roles', 'imagina-woo-quotes' ),
							'type'  => 'roles',
						),
						'hide_add_to_cart' => array(
							'label' => __( 'Ocultar el botón de compra', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
							'desc'  => __( 'El producto deja de ser comprable de verdad, no solo se esconde el botón.', 'imagina-woo-quotes' ),
						),
						'hide_add_to_cart_roles' => array(
							'label' => __( 'Ocultar la compra solo a estos roles', 'imagina-woo-quotes' ),
							'type'  => 'roles',
						),
					),
				),
			),

			'quote' => array(
				'lifecycle' => array(
					'title'  => __( 'Ciclo de vida', 'imagina-woo-quotes' ),
					'fields' => array(
						'auto_expire'          => array(
							'label' => __( 'Los presupuestos vencen', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'expiry_days'          => array(
							'label' => __( 'Días de validez', 'imagina-woo-quotes' ),
							'type'  => 'number',
						),
						'reminders_enabled'    => array(
							'label' => __( 'Enviar recordatorio antes de vencer', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'reminder_days_before' => array(
							'label' => __( 'Días de antelación del recordatorio', 'imagina-woo-quotes' ),
							'type'  => 'number',
						),
						'allow_counter_offers' => array(
							'label' => __( 'Permitir contraofertas del cliente', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
							'desc'  => __( 'El cliente puede proponer otro precio en lugar de solo aceptar o rechazar.', 'imagina-woo-quotes' ),
						),
						'redirect_to_payment'  => array(
							'label' => __( 'Llevar al pago tras aceptar', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
					),
				),
				'limits' => array(
					'title'  => __( 'Cantidades', 'imagina-woo-quotes' ),
					'fields' => array(
						'min_quantity' => array(
							'label' => __( 'Cantidad mínima por línea', 'imagina-woo-quotes' ),
							'type'  => 'number',
							'desc'  => __( 'Cero para no imponer mínimo.', 'imagina-woo-quotes' ),
						),
						'max_quantity' => array(
							'label' => __( 'Cantidad máxima por línea', 'imagina-woo-quotes' ),
							'type'  => 'number',
							'desc'  => __( 'Cero para no imponer máximo.', 'imagina-woo-quotes' ),
						),
					),
				),
			),

			'form' => array(
				'builder' => array(
					'title'  => __( 'Campos del formulario', 'imagina-woo-quotes' ),
					'fields' => array(
						'form_fields' => array(
							'label' => '',
							'type'  => 'form_builder',
						),
					),
				),
				'texts' => array(
					'title'  => __( 'Textos', 'imagina-woo-quotes' ),
					'fields' => array(
						'form_title'         => array(
							'label' => __( 'Título sobre el formulario', 'imagina-woo-quotes' ),
							'type'  => 'text',
						),
						'submit_label'       => array(
							'label' => __( 'Texto del botón de envío', 'imagina-woo-quotes' ),
							'type'  => 'text',
						),
						'success_message'    => array(
							'label' => __( 'Mensaje tras enviar', 'imagina-woo-quotes' ),
							'type'  => 'textarea',
						),
						'form_privacy_text'  => array(
							'label' => __( 'Texto de privacidad', 'imagina-woo-quotes' ),
							'type'  => 'textarea',
							'desc'  => __( 'Usa [privacy_policy] para enlazar la política de privacidad.', 'imagina-woo-quotes' ),
						),
						'show_form_when_empty' => array(
							'label' => __( 'Mostrar el formulario con la lista vacía', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'autocomplete_form'  => array(
							'label' => __( 'Precargar los datos del cliente registrado', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
					),
				),
				'captcha' => array(
					'title'  => __( 'reCAPTCHA', 'imagina-woo-quotes' ),
					'fields' => array(
						'recaptcha_enabled'    => array(
							'label' => __( 'Activar reCAPTCHA', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
							'desc'  => __( 'El script de Google solo se carga en la página del formulario.', 'imagina-woo-quotes' ),
						),
						'recaptcha_version'    => array(
							'label'   => __( 'Versión', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'v3' => __( 'v3 (invisible)', 'imagina-woo-quotes' ),
								'v2' => __( 'v2 (casilla)', 'imagina-woo-quotes' ),
							),
						),
						'recaptcha_site_key'   => array(
							'label' => __( 'Clave del sitio', 'imagina-woo-quotes' ),
							'type'  => 'text',
						),
						'recaptcha_secret_key' => array(
							'label' => __( 'Clave secreta', 'imagina-woo-quotes' ),
							'type'  => 'password',
						),
						'recaptcha_threshold'  => array(
							'label' => __( 'Puntuación mínima (v3)', 'imagina-woo-quotes' ),
							'type'  => 'text',
							'desc'  => __( 'Entre 0 y 1. Por defecto 0,5.', 'imagina-woo-quotes' ),
						),
					),
				),
				'uploads' => array(
					'title'  => __( 'Adjuntos', 'imagina-woo-quotes' ),
					'fields' => array(
						'upload_max_size' => array(
							'label' => __( 'Tamaño máximo por archivo (MB)', 'imagina-woo-quotes' ),
							'type'  => 'number',
						),
					),
				),
			),

			'pdf' => array(
				'main' => array(
					'title'  => __( 'Documento', 'imagina-woo-quotes' ),
					'fields' => array(
						'pdf_enabled'          => array(
							'label' => __( 'Generar PDF de los presupuestos', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'pdf_attach_to_email'  => array(
							'label' => __( 'Adjuntarlo al email del presupuesto', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'pdf_template_id'      => array(
							'label' => __( 'Plantilla', 'imagina-woo-quotes' ),
							'type'  => 'pdf_template',
						),
						'pdf_logo_id'          => array(
							'label' => __( 'Logotipo', 'imagina-woo-quotes' ),
							'type'  => 'media',
							'desc'  => __( 'Si lo dejas vacío se usa el logo del tema.', 'imagina-woo-quotes' ),
						),
						'pdf_filename'         => array(
							'label' => __( 'Nombre del archivo', 'imagina-woo-quotes' ),
							'type'  => 'text',
							'desc'  => __( 'Admite {order_number}, {order_id}, {date} y {site_title}.', 'imagina-woo-quotes' ),
						),
						'pdf_paper_size'       => array(
							'label'   => __( 'Tamaño', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'A4'     => 'A4',
								'letter' => __( 'Carta', 'imagina-woo-quotes' ),
								'legal'  => __( 'Oficio', 'imagina-woo-quotes' ),
							),
						),
						'pdf_orientation'      => array(
							'label'   => __( 'Orientación', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'portrait'  => __( 'Vertical', 'imagina-woo-quotes' ),
								'landscape' => __( 'Horizontal', 'imagina-woo-quotes' ),
							),
						),
						'pdf_show_actions'     => array(
							'label' => __( 'Incluir los enlaces de aceptar y rechazar', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'pdf_show_strikethrough' => array(
							'label' => __( 'Tachar el precio de catálogo si el presupuesto mejora', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'pdf_show_footer'      => array(
							'label' => __( 'Mostrar pie de página', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'pdf_footer_text'      => array(
							'label' => __( 'Texto del pie', 'imagina-woo-quotes' ),
							'type'  => 'textarea',
						),
						'pdf_custom_css'       => array(
							'label' => __( 'CSS adicional', 'imagina-woo-quotes' ),
							'type'  => 'textarea',
							'desc'  => __( 'dompdf admite un subconjunto de CSS 2.1: nada de flexbox ni grid.', 'imagina-woo-quotes' ),
						),
					),
				),
			),

			'rules' => array(
				'scope' => array(
					'title'  => __( 'Alcance', 'imagina-woo-quotes' ),
					'fields' => array(
						'scope'      => array(
							'label'   => __( 'Ofrecer presupuesto en', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'all'   => __( 'Todo el catálogo', 'imagina-woo-quotes' ),
								'rules' => __( 'Solo lo que indiquen las inclusiones', 'imagina-woo-quotes' ),
							),
						),
						'stock_rule' => array(
							'label'   => __( 'Según el stock', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'any'          => __( 'Sin condición', 'imagina-woo-quotes' ),
								'out_of_stock' => __( 'Solo productos agotados', 'imagina-woo-quotes' ),
								'in_stock'     => __( 'Solo productos con stock', 'imagina-woo-quotes' ),
							),
						),
						'allow_external_grouped' => array(
							'label' => __( 'Incluir productos externos y agrupados', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
					),
				),
				'include' => array(
					'title'  => __( 'Inclusiones', 'imagina-woo-quotes' ),
					'fields' => array(
						'included_categories' => array(
							'label'    => __( 'Categorías', 'imagina-woo-quotes' ),
							'type'     => 'terms',
							'taxonomy' => 'product_cat',
						),
						'included_tags'       => array(
							'label'    => __( 'Etiquetas', 'imagina-woo-quotes' ),
							'type'     => 'terms',
							'taxonomy' => 'product_tag',
						),
						'included_products'   => array(
							'label' => __( 'Productos', 'imagina-woo-quotes' ),
							'type'  => 'products',
						),
					),
				),
				'exclude' => array(
					'title'  => __( 'Exclusiones', 'imagina-woo-quotes' ),
					'fields' => array(
						'excluded_categories' => array(
							'label'    => __( 'Categorías', 'imagina-woo-quotes' ),
							'type'     => 'terms',
							'taxonomy' => 'product_cat',
						),
						'excluded_tags'       => array(
							'label'    => __( 'Etiquetas', 'imagina-woo-quotes' ),
							'type'     => 'terms',
							'taxonomy' => 'product_tag',
						),
						'excluded_products'   => array(
							'label' => __( 'Productos', 'imagina-woo-quotes' ),
							'type'  => 'products',
						),
					),
				),
			),

			'stats' => array(),
		);

		return isset( $sections[ $tab ] ) ? $sections[ $tab ] : array();
	}

	/**
	 * Registra todos los ajustes con su función de saneado.
	 *
	 * @return void
	 */
	public static function register() {
		foreach ( array_keys( self::get_tabs() ) as $tab ) {
			foreach ( self::get_sections( $tab ) as $section ) {
				foreach ( $section['fields'] as $key => $field ) {
					register_setting(
						self::OPTION_GROUP . '_' . $tab,
						'iwq_' . $key,
						array(
							'sanitize_callback' => self::get_sanitizer( $field ),
							'default'           => '',
						)
					);
				}
			}
		}
	}

	/**
	 * Devuelve la función de saneado adecuada a un tipo de campo.
	 *
	 * @param array $field Definición del campo.
	 * @return callable
	 */
	private static function get_sanitizer( $field ) {
		switch ( $field['type'] ) {
			case 'checkbox':
				return static function ( $value ) {
					return 'yes' === $value ? 'yes' : 'no';
				};

			case 'number':
			case 'page':
			case 'media':
			case 'pdf_template':
				return 'absint';

			case 'textarea':
				return 'sanitize_textarea_field';

			case 'roles':
			case 'terms':
			case 'products':
				return array( __CLASS__, 'sanitize_list' );

			case 'form_builder':
				return array( __CLASS__, 'sanitize_form_fields' );

			default:
				return 'sanitize_text_field';
		}
	}

	/**
	 * Sanea una lista de identificadores.
	 *
	 * @param mixed $value Valor recibido.
	 * @return array
	 */
	public static function sanitize_list( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'sanitize_text_field', $value ) ) );
	}

	/**
	 * Sanea la definición completa del formulario.
	 *
	 * @param mixed $value Valor recibido del constructor.
	 * @return array
	 */
	public static function sanitize_form_fields( $value ) {
		if ( ! is_array( $value ) ) {
			return iwq_get_default_form_fields();
		}

		$types  = array_keys( iwq_get_form_field_types() );
		$widths = array_keys( iwq_get_form_field_widths() );
		$clean  = array();

		foreach ( $value as $field ) {
			$id = isset( $field['id'] ) ? sanitize_key( $field['id'] ) : '';

			if ( ! $id ) {
				continue;
			}

			$type = isset( $field['type'] ) && in_array( $field['type'], $types, true ) ? $field['type'] : 'text';

			$clean[ $id ] = array(
				'id'          => $id,
				'type'        => $type,
				'label'       => isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : '',
				'placeholder' => isset( $field['placeholder'] ) ? sanitize_text_field( $field['placeholder'] ) : '',
				'description' => isset( $field['description'] ) ? sanitize_text_field( $field['description'] ) : '',
				'required'    => ! empty( $field['required'] ) ? 'yes' : 'no',
				'enabled'     => ! empty( $field['enabled'] ) ? 'yes' : 'no',
				'width'       => isset( $field['width'] ) && in_array( $field['width'], $widths, true ) ? $field['width'] : 'full',
				'connect_to'  => isset( $field['connect_to'] ) ? sanitize_key( $field['connect_to'] ) : '',
				'options'     => self::sanitize_field_options( self::extract_options( $field ) ),
				'default'     => isset( $field['default'] ) ? sanitize_text_field( $field['default'] ) : '',
				'core'        => ! empty( $field['core'] ),
				'max_size'    => isset( $field['max_size'] ) ? absint( $field['max_size'] ) : 0,
				'extensions'  => isset( $field['extensions'] ) ? sanitize_text_field( $field['extensions'] ) : '',
				'min_date'    => isset( $field['min_date'] ) ? sanitize_text_field( $field['min_date'] ) : '',
				'max_date'    => isset( $field['max_date'] ) ? sanitize_text_field( $field['max_date'] ) : '',
			);
		}

		return $clean ? $clean : iwq_get_default_form_fields();
	}

	/**
	 * Extrae las opciones de un campo, vengan de la caja de texto del
	 * constructor (una por línea) o ya estructuradas.
	 *
	 * @param array $field Campo recibido del formulario.
	 * @return array
	 */
	private static function extract_options( $field ) {
		if ( isset( $field['options_raw'] ) && is_string( $field['options_raw'] ) ) {
			$lines = preg_split( '/\r\n|\r|\n/', $field['options_raw'] );

			return array_values( array_filter( array_map( 'trim', (array) $lines ) ) );
		}

		return isset( $field['options'] ) ? $field['options'] : array();
	}

	/**
	 * Sanea las opciones de un campo de selección.
	 *
	 * @param mixed $options Opciones recibidas.
	 * @return array
	 */
	private static function sanitize_field_options( $options ) {
		if ( ! is_array( $options ) ) {
			return array();
		}

		$clean = array();

		foreach ( $options as $option ) {
			if ( is_array( $option ) && isset( $option['value'] ) ) {
				$clean[] = array(
					'value' => sanitize_text_field( $option['value'] ),
					'label' => isset( $option['label'] ) ? sanitize_text_field( $option['label'] ) : sanitize_text_field( $option['value'] ),
				);
			} elseif ( is_string( $option ) && '' !== trim( $option ) ) {
				$clean[] = array(
					'value' => sanitize_title( $option ),
					'label' => sanitize_text_field( $option ),
				);
			}
		}

		return $clean;
	}

	/**
	 * Pinta la página de ajustes.
	 *
	 * @return void
	 */
	public static function render_page() {
		$tabs = self::get_tabs();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo elige la pestaña visible.
		$current = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		$current = isset( $tabs[ $current ] ) ? $current : 'general';

		iwq_get_template(
			'admin/settings-page.php',
			array(
				'tabs'     => $tabs,
				'current'  => $current,
				'sections' => self::get_sections( $current ),
			)
		);
	}
}
