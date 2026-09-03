<?php
/**
 * Página de ajustes del plugin.
 *
 * Se apoya en la API de ajustes de WordPress (registro, nonces y saneado)
 * con una interfaz propia y ligera que solo se carga en esta pantalla, sin
 * ningún framework: es la diferencia entre pesar unos kilobytes y arrastrar
 * varios megas en cada página del admin.
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
		$tabs = array();

		foreach ( self::get_tab_meta() as $slug => $tab ) {
			$tabs[ $slug ] = $tab['label'];
		}

		return $tabs;
	}

	/**
	 * Metadatos de cada pestaña: etiqueta, grupo del menú, icono y texto de
	 * cabecera. Las pestañas sin campos (inicio, vista previa, estadísticas)
	 * pintan su propia plantilla.
	 *
	 * @return array<string,array{label:string,group:string,icon:string,desc:string}>
	 */
	public static function get_tab_meta() {
		return array(
			'inicio'  => array(
				'label' => __( 'Inicio', 'imagina-woo-quotes' ),
				'group' => __( 'Panel', 'imagina-woo-quotes' ),
				'icon'  => 'home',
				'desc'  => __( 'Un vistazo a la actividad reciente y a lo que falta por configurar.', 'imagina-woo-quotes' ),
			),
			'stats'   => array(
				'label' => __( 'Estadísticas', 'imagina-woo-quotes' ),
				'group' => __( 'Panel', 'imagina-woo-quotes' ),
				'icon'  => 'chart',
				'desc'  => __( 'Cuántas solicitudes entran, cuántas se aceptan y qué productos generan más interés.', 'imagina-woo-quotes' ),
			),
			'preview' => array(
				'label' => __( 'Vista previa', 'imagina-woo-quotes' ),
				'group' => __( 'Panel', 'imagina-woo-quotes' ),
				'icon'  => 'eye',
				'desc'  => __( 'Mira cada email y el PDF exactamente como los recibe el cliente o el administrador.', 'imagina-woo-quotes' ),
			),
			'general' => array(
				'label' => __( 'General', 'imagina-woo-quotes' ),
				'group' => __( 'Ajustes', 'imagina-woo-quotes' ),
				'icon'  => 'sliders',
				'desc'  => __( 'Activación, páginas y quién puede pedir presupuesto.', 'imagina-woo-quotes' ),
			),
			'display' => array(
				'label' => __( 'Botones y catálogo', 'imagina-woo-quotes' ),
				'group' => __( 'Ajustes', 'imagina-woo-quotes' ),
				'icon'  => 'tag',
				'desc'  => __( 'Dónde aparece el botón y cómo se comportan los precios y la compra.', 'imagina-woo-quotes' ),
			),
			'design'  => array(
				'label' => __( 'Diseño', 'imagina-woo-quotes' ),
				'group' => __( 'Ajustes', 'imagina-woo-quotes' ),
				'icon'  => 'palette',
				'desc'  => __( 'Colores, tipografía y forma del botón, del panel lateral y de la página de solicitud. Los cambios se ven en la vista previa al momento.', 'imagina-woo-quotes' ),
			),
			'quote'   => array(
				'label' => __( 'Presupuestos', 'imagina-woo-quotes' ),
				'group' => __( 'Ajustes', 'imagina-woo-quotes' ),
				'icon'  => 'file',
				'desc'  => __( 'Caducidad, recordatorios, contraofertas y cantidades permitidas.', 'imagina-woo-quotes' ),
			),
			'form'    => array(
				'label' => __( 'Formulario', 'imagina-woo-quotes' ),
				'group' => __( 'Ajustes', 'imagina-woo-quotes' ),
				'icon'  => 'form',
				'desc'  => __( 'Los campos que rellena el cliente, los textos y la protección contra spam.', 'imagina-woo-quotes' ),
			),
			'pdf'     => array(
				'label' => __( 'PDF', 'imagina-woo-quotes' ),
				'group' => __( 'Ajustes', 'imagina-woo-quotes' ),
				'icon'  => 'pdf',
				'desc'  => __( 'El documento que acompaña a cada presupuesto: plantilla, logotipo y formato.', 'imagina-woo-quotes' ),
			),
			'emails'  => array(
				'label' => __( 'Emails', 'imagina-woo-quotes' ),
				'group' => __( 'Ajustes', 'imagina-woo-quotes' ),
				'icon'  => 'mail',
				'desc'  => __( 'Diseño, color y logotipo de todos los emails del plugin.', 'imagina-woo-quotes' ),
			),
			'rules'   => array(
				'label' => __( 'Reglas', 'imagina-woo-quotes' ),
				'group' => __( 'Ajustes', 'imagina-woo-quotes' ),
				'icon'  => 'filter',
				'desc'  => __( 'Qué productos, categorías y roles pueden pedir presupuesto.', 'imagina-woo-quotes' ),
			),
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

			'design' => array(
				'preview' => array(
					'title'  => __( 'Vista previa', 'imagina-woo-quotes' ),
					'desc'   => __( 'Una muestra del botón, el panel lateral y el formulario con los ajustes de esta pestaña. Se actualiza al cambiar cualquier valor, antes de guardar.', 'imagina-woo-quotes' ),
					'fields' => array(
						'design_preview' => array(
							'label' => '',
							'type'  => 'design_preview',
						),
					),
				),
				'colors' => array(
					'title'  => __( 'Colores y forma', 'imagina-woo-quotes' ),
					'desc'   => __( 'Se aplican a todo lo que pinta el plugin en la tienda. Déjalos vacíos para usar los valores por defecto.', 'imagina-woo-quotes' ),
					'fields' => array(
						'design_accent'          => array(
							'label' => __( 'Color principal', 'imagina-woo-quotes' ),
							'type'  => 'color',
							'desc'  => __( 'Botones, contador y bordes activos.', 'imagina-woo-quotes' ),
						),
						'design_accent_hover'    => array(
							'label' => __( 'Color principal al pasar el ratón', 'imagina-woo-quotes' ),
							'type'  => 'color',
						),
						'design_accent_contrast' => array(
							'label' => __( 'Texto sobre el color principal', 'imagina-woo-quotes' ),
							'type'  => 'color',
						),
						'design_text'            => array(
							'label' => __( 'Texto', 'imagina-woo-quotes' ),
							'type'  => 'color',
						),
						'design_text_muted'      => array(
							'label' => __( 'Texto secundario', 'imagina-woo-quotes' ),
							'type'  => 'color',
						),
						'design_surface'         => array(
							'label' => __( 'Fondo', 'imagina-woo-quotes' ),
							'type'  => 'color',
							'desc'  => __( 'Panel lateral, campos y tarjetas.', 'imagina-woo-quotes' ),
						),
						'design_surface_alt'     => array(
							'label' => __( 'Fondo secundario', 'imagina-woo-quotes' ),
							'type'  => 'color',
							'desc'  => __( 'Pie del panel, campos rellenos y avisos.', 'imagina-woo-quotes' ),
						),
						'design_border'          => array(
							'label' => __( 'Bordes', 'imagina-woo-quotes' ),
							'type'  => 'color',
						),
						'design_radius'          => array(
							'label' => __( 'Redondeo general', 'imagina-woo-quotes' ),
							'type'  => 'size',
							'unit'  => 'px',
							'max'   => 40,
							'desc'  => __( 'Esquinas de campos, tarjetas y miniaturas. El botón puede tener el suyo.', 'imagina-woo-quotes' ),
						),
						'design_dark_mode'       => array(
							'label' => __( 'Adaptarse al modo oscuro del sistema', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
							'desc'  => __( 'Invierte fondos y textos cuando el visitante tiene el modo oscuro activado. Actívalo solo si tu tema también lo hace.', 'imagina-woo-quotes' ),
						),
					),
				),
				'button' => array(
					'title'  => __( 'Botón «Solicitar presupuesto»', 'imagina-woo-quotes' ),
					'desc'   => __( 'También se aplica al botón de enviar del formulario y a los de aceptar y rechazar.', 'imagina-woo-quotes' ),
					'fields' => array(
						'button_style'          => array(
							'label'   => __( 'Estilo', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'solid'   => __( 'Relleno', 'imagina-woo-quotes' ),
								'outline' => __( 'Contorno', 'imagina-woo-quotes' ),
							),
						),
						'button_font'           => array(
							'label'   => __( 'Tipografía', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'inherit' => __( 'La del tema', 'imagina-woo-quotes' ),
								'system'  => __( 'Del sistema (San Francisco, Segoe UI, Roboto…)', 'imagina-woo-quotes' ),
							),
						),
						'button_font_size'      => array(
							'label'       => __( 'Tamaño de letra', 'imagina-woo-quotes' ),
							'type'        => 'size',
							'unit'        => 'px',
							'max'         => 40,
							'placeholder' => __( 'Heredar', 'imagina-woo-quotes' ),
						),
						'button_font_weight'    => array(
							'label'   => __( 'Grosor', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'inherit' => __( 'Seminegrita (por defecto)', 'imagina-woo-quotes' ),
								'400'     => __( 'Normal', 'imagina-woo-quotes' ),
								'500'     => __( 'Medio', 'imagina-woo-quotes' ),
								'600'     => __( 'Seminegrita', 'imagina-woo-quotes' ),
								'700'     => __( 'Negrita', 'imagina-woo-quotes' ),
							),
						),
						'button_text_transform' => array(
							'label'   => __( 'Mayúsculas', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'none'      => __( 'Tal como se escribe', 'imagina-woo-quotes' ),
								'uppercase' => __( 'Todo en mayúsculas', 'imagina-woo-quotes' ),
							),
						),
						'button_padding_y'      => array(
							'label'       => __( 'Relleno vertical', 'imagina-woo-quotes' ),
							'type'        => 'size',
							'unit'        => 'px',
							'max'         => 60,
							'placeholder' => __( 'Heredar', 'imagina-woo-quotes' ),
						),
						'button_padding_x'      => array(
							'label'       => __( 'Relleno horizontal', 'imagina-woo-quotes' ),
							'type'        => 'size',
							'unit'        => 'px',
							'max'         => 120,
							'placeholder' => __( 'Heredar', 'imagina-woo-quotes' ),
						),
						'button_radius'         => array(
							'label'       => __( 'Redondeo del botón', 'imagina-woo-quotes' ),
							'type'        => 'size',
							'unit'        => 'px',
							'max'         => 999,
							'placeholder' => __( 'Como el general', 'imagina-woo-quotes' ),
							'desc'        => __( 'Un valor alto, como 999, da un botón en forma de píldora.', 'imagina-woo-quotes' ),
						),
						'button_shadow'         => array(
							'label' => __( 'Sombra', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'button_full_width'     => array(
							'label' => __( 'Ancho completo en la ficha de producto', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'link_color'            => array(
							'label' => __( 'Color de los enlaces', 'imagina-woo-quotes' ),
							'type'  => 'color',
							'desc'  => __( 'Nombres de producto en la lista y enlaces de texto como «Vaciar la lista».', 'imagina-woo-quotes' ),
						),
						'link_hover_color'      => array(
							'label' => __( 'Color de los enlaces al pasar el ratón', 'imagina-woo-quotes' ),
							'type'  => 'color',
						),
					),
				),
				'drawer' => array(
					'title'  => __( 'Panel lateral', 'imagina-woo-quotes' ),
					'desc'   => __( 'El panel que se abre al añadir un producto.', 'imagina-woo-quotes' ),
					'fields' => array(
						'drawer_title'        => array(
							'label'       => __( 'Título', 'imagina-woo-quotes' ),
							'type'        => 'text',
							'placeholder' => __( 'Tu presupuesto', 'imagina-woo-quotes' ),
						),
						'drawer_footer_label' => array(
							'label'       => __( 'Texto del botón inferior', 'imagina-woo-quotes' ),
							'type'        => 'text',
							'placeholder' => __( 'Ver y enviar la solicitud', 'imagina-woo-quotes' ),
						),
						'drawer_position'     => array(
							'label'   => __( 'Lado', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'right' => __( 'Derecha', 'imagina-woo-quotes' ),
								'left'  => __( 'Izquierda', 'imagina-woo-quotes' ),
							),
						),
						'drawer_width'        => array(
							'label' => __( 'Ancho', 'imagina-woo-quotes' ),
							'type'  => 'size',
							'unit'  => 'px',
							'min'   => 280,
							'max'   => 720,
							'desc'  => __( 'El mini carrito de WooCommerce mide 480 px.', 'imagina-woo-quotes' ),
						),
						'drawer_header_style' => array(
							'label'   => __( 'Cabecera', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'plain'  => __( 'Sobre el fondo', 'imagina-woo-quotes' ),
								'accent' => __( 'Con el color principal', 'imagina-woo-quotes' ),
							),
						),
						'drawer_overlay'      => array(
							'label' => __( 'Oscurecer el fondo', 'imagina-woo-quotes' ),
							'type'  => 'size',
							'unit'  => '%',
							'max'   => 100,
							'desc'  => __( 'Opacidad del velo sobre la página mientras el panel está abierto.', 'imagina-woo-quotes' ),
						),
						'drawer_show_thumbs'  => array(
							'label' => __( 'Mostrar miniaturas', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'drawer_thumb_size'   => array(
							'label' => __( 'Tamaño de la miniatura', 'imagina-woo-quotes' ),
							'type'  => 'size',
							'unit'  => 'px',
							'min'   => 24,
							'max'   => 200,
						),
					),
				),
				'page' => array(
					'title'  => __( 'Página de solicitud', 'imagina-woo-quotes' ),
					'desc'   => __( 'La página con la lista de productos y el formulario. Los textos del formulario se cambian en la pestaña Formulario.', 'imagina-woo-quotes' ),
					'fields' => array(
						'page_list_style' => array(
							'label'   => __( 'Lista de productos', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'woocommerce' => __( 'Como el carrito de WooCommerce', 'imagina-woo-quotes' ),
								'plugin'      => __( 'Lista compacta del plugin', 'imagina-woo-quotes' ),
							),
							'desc'    => __( 'Con la tabla del carrito, la lista, la caja de cantidad, el aspa de quitar y los botones usan las mismas clases que el carrito, así que el tema los pinta idénticos. La lista compacta es la del panel lateral, con los colores de esta pestaña.', 'imagina-woo-quotes' ),
						),
						'page_layout'     => array(
							'label'   => __( 'Disposición', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'stacked'           => __( 'Lista arriba, formulario debajo', 'imagina-woo-quotes' ),
								'columns'           => __( 'Dos columnas: lista y formulario', 'imagina-woo-quotes' ),
								'columns_form_left' => __( 'Dos columnas: formulario y lista', 'imagina-woo-quotes' ),
							),
							'desc'    => __( 'En pantallas estrechas siempre se apilan.', 'imagina-woo-quotes' ),
						),
						'page_width'      => array(
							'label'   => __( 'Ancho en escritorio', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'auto'    => __( 'Automático: amplio con dos columnas, normal con una', 'imagina-woo-quotes' ),
								'content' => __( 'El del contenido del tema', 'imagina-woo-quotes' ),
								'wide'    => __( 'Amplio (alignwide)', 'imagina-woo-quotes' ),
								'full'    => __( 'Completo (alignfull)', 'imagina-woo-quotes' ),
							),
							'desc'    => __( 'Los anchos amplio y completo los definen los temas de bloques y los clásicos con soporte de alineaciones anchas. Si usas el bloque «Lista de presupuesto», su alineación en el editor manda sobre este ajuste.', 'imagina-woo-quotes' ),
						),
						'page_columns'    => array(
							'label'   => __( 'Reparto de las columnas', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'30' => __( 'Lista 30 % · Formulario 70 %', 'imagina-woo-quotes' ),
								'40' => __( 'Lista 40 % · Formulario 60 %', 'imagina-woo-quotes' ),
								'50' => __( 'Mitad y mitad', 'imagina-woo-quotes' ),
								'60' => __( 'Lista 60 % · Formulario 40 %', 'imagina-woo-quotes' ),
								'70' => __( 'Lista 70 % · Formulario 30 %', 'imagina-woo-quotes' ),
							),
							'desc'    => __( 'Solo con dos columnas. El orden lo decide la disposición.', 'imagina-woo-quotes' ),
						),
						'page_columns_gap' => array(
							'label' => __( 'Separación entre columnas', 'imagina-woo-quotes' ),
							'type'  => 'size',
							'unit'  => 'px',
							'max'   => 160,
						),
						'page_sticky_list' => array(
							'label' => __( 'Fijar la lista al hacer scroll', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
							'desc'  => __( 'Con dos columnas, la lista de productos acompaña al formulario mientras se rellena.', 'imagina-woo-quotes' ),
						),
						'page_card_style' => array(
							'label'   => __( 'Bloques', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'plain'    => __( 'Sin fondo', 'imagina-woo-quotes' ),
								'bordered' => __( 'Tarjetas con borde', 'imagina-woo-quotes' ),
								'shadow'   => __( 'Tarjetas con sombra', 'imagina-woo-quotes' ),
							),
						),
						'page_list_title' => array(
							'label'       => __( 'Título de la lista', 'imagina-woo-quotes' ),
							'type'        => 'text',
							'placeholder' => __( 'Productos en tu solicitud', 'imagina-woo-quotes' ),
						),
						'page_show_thumbs' => array(
							'label' => __( 'Mostrar miniaturas', 'imagina-woo-quotes' ),
							'type'  => 'checkbox',
						),
						'page_thumb_size'  => array(
							'label' => __( 'Tamaño de la miniatura', 'imagina-woo-quotes' ),
							'type'  => 'size',
							'unit'  => 'px',
							'min'   => 24,
							'max'   => 300,
							'desc'  => __( 'Ancho de la imagen en la lista. WooCommerce genera la miniatura a 300 px y el tema no siempre la limita fuera del carrito.', 'imagina-woo-quotes' ),
						),
						'page_thumb_radius' => array(
							'label' => __( 'Redondeo de la miniatura', 'imagina-woo-quotes' ),
							'type'  => 'size',
							'unit'  => 'px',
							'max'   => 150,
						),
						'field_style'     => array(
							'label'   => __( 'Estilo de los campos', 'imagina-woo-quotes' ),
							'type'    => 'select',
							'options' => array(
								'default'   => __( 'Con borde', 'imagina-woo-quotes' ),
								'filled'    => __( 'Rellenos', 'imagina-woo-quotes' ),
								'underline' => __( 'Solo línea inferior', 'imagina-woo-quotes' ),
							),
						),
						'field_radius'    => array(
							'label'       => __( 'Redondeo de los campos', 'imagina-woo-quotes' ),
							'type'        => 'size',
							'unit'        => 'px',
							'max'         => 40,
							'placeholder' => __( 'Como el general', 'imagina-woo-quotes' ),
						),
					),
				),
				'css' => array(
					'title'  => __( 'CSS adicional', 'imagina-woo-quotes' ),
					'desc'   => __( 'Para lo que no cubran los ajustes. Se imprime solo en las páginas donde carga el plugin, después de sus estilos.', 'imagina-woo-quotes' ),
					'fields' => array(
						'custom_css' => array(
							'label'       => __( 'CSS del front', 'imagina-woo-quotes' ),
							'type'        => 'css',
							'placeholder' => ".iwq-add-button {\n\tletter-spacing: 0.02em;\n}",
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

			'emails' => array(
				'design' => array(
					'title'  => __( 'Diseño de los emails', 'imagina-woo-quotes' ),
					'fields' => array(
						'email_style'       => array(
							'label' => __( 'Diseño', 'imagina-woo-quotes' ),
							'type'  => 'email_style',
							'desc'  => __( 'Se aplica a los seis emails del plugin. Compruébalo en la pestaña «Vista previa».', 'imagina-woo-quotes' ),
						),
						'email_accent'      => array(
							'label' => __( 'Color de acento', 'imagina-woo-quotes' ),
							'type'  => 'color',
							'desc'  => __( 'Botones y barra superior. Vacío: azul por defecto, o el color de WooCommerce en el diseño «Como WooCommerce».', 'imagina-woo-quotes' ),
						),
						'email_logo_id'     => array(
							'label' => __( 'Logotipo', 'imagina-woo-quotes' ),
							'type'  => 'media',
							'desc'  => __( 'Si lo dejas vacío se usa el del PDF o el del tema.', 'imagina-woo-quotes' ),
						),
						'email_footer_text' => array(
							'label' => __( 'Pie de los emails', 'imagina-woo-quotes' ),
							'type'  => 'textarea',
							'desc'  => __( 'Vacío: nombre y dirección de la tienda. No se usa en el diseño «Como WooCommerce», que lleva el pie de la tienda.', 'imagina-woo-quotes' ),
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

			'stats'   => array(),
			'preview' => array(),
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
					if ( 'design_preview' === $field['type'] ) {
						continue;
					}

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

			case 'color':
				return static function ( $value ) {
					$value = sanitize_hex_color( $value );
					return $value ? $value : '';
				};

			case 'number':
			case 'page':
			case 'media':
			case 'pdf_template':
				return 'absint';

			case 'size':
				// A diferencia de «number», el vacío se conserva: significa
				// «heredar», y un cero sería un tamaño real.
				return static function ( $value ) {
					$value = trim( (string) $value );
					return '' === $value ? '' : (string) absint( $value );
				};

			case 'css':
				return array( IWQ_Design::class, 'sanitize_css' );

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
		$current = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'inicio';
		$current = isset( $tabs[ $current ] ) ? $current : 'inicio';

		iwq_get_template(
			'admin/settings-page.php',
			array(
				'tabs'     => self::get_tab_meta(),
				'current'  => $current,
				'sections' => self::get_sections( $current ),
			)
		);
	}
}
