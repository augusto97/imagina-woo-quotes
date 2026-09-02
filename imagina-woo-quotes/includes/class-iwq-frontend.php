<?php
/**
 * Integración con las plantillas de WooCommerce en el front.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Frontend
 */
class IWQ_Frontend {

	/**
	 * Marca si en esta petición se ha pintado algún botón.
	 *
	 * Los assets solo se encolan si la respuesta realmente los necesita.
	 *
	 * @var bool
	 */
	private static $needs_assets = false;

	/**
	 * Productos para los que ya se pintó el botón de ficha en esta petición.
	 *
	 * En un tema de bloques el botón puede llegar por el hook clásico y por
	 * el filtro del bloque de compra; con esto solo sale una vez.
	 *
	 * @var array<int,bool>
	 */
	private static $rendered_single = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Ficha de producto.
		$position = iwq_get_option( 'button_position_single', 'after_add_to_cart' );
		$hook     = 'before_add_to_cart' === $position
			? 'woocommerce_before_add_to_cart_form'
			: 'woocommerce_after_add_to_cart_form';

		add_action( $hook, array( $this, 'render_single_button' ), 20 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_single_button_fallback' ), 31 );

		// Temas de bloques: cuando el producto no es comprable (agotado, sin
		// precio) el bloque de compra no dispara ningún hook clásico, así que
		// añadimos el botón a la salida del propio bloque.
		add_filter( 'render_block_woocommerce/add-to-cart-form', array( $this, 'append_to_add_to_cart_block' ), 10, 2 );
		add_filter( 'render_block_woocommerce/add-to-cart-with-options', array( $this, 'append_to_add_to_cart_block' ), 10, 2 );

		// Catálogo.
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'render_loop_button' ), 20 );

		// Ocultación de precio y botón de compra.
		add_filter( 'woocommerce_get_price_html', array( $this, 'filter_price_html' ), 100, 2 );
		add_filter( 'woocommerce_is_purchasable', array( $this, 'filter_is_purchasable' ), 100, 2 );
		add_filter( 'woocommerce_variable_price_html', array( $this, 'filter_price_html' ), 100, 2 );

		// Carrito: permite pasar el carrito entero a presupuesto. El hook cubre
		// el carrito clásico; el filtro, el bloque Carrito de los temas de
		// bloques, que no dispara ningún hook clásico.
		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'render_cart_button' ), 25 );
		add_filter( 'render_block_woocommerce/cart', array( $this, 'append_cart_button_to_block' ), 10, 2 );

		// Contador en el menú y panel lateral.
		add_action( 'wp_footer', array( $this, 'render_drawer' ) );

		// Paso del carrito completo a la lista de presupuesto.
		add_action( 'template_redirect', array( $this, 'handle_cart_to_quote' ) );
	}

	/**
	 * Pasa el contenido del carrito a la lista de presupuesto.
	 *
	 * Se saltan los productos que las reglas no permiten presupuestar y se
	 * avisa de cuántos quedaron fuera, para que el cliente no crea que se
	 * han perdido.
	 *
	 * @return void
	 */
	public function handle_cart_to_quote() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- el nonce se comprueba justo debajo.
		if ( empty( $_GET['iwq_cart_to_quote'] ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'iwq_cart_to_quote' ) ) {
			wc_add_notice( __( 'Ese enlace ya no es válido. Inténtalo de nuevo.', 'imagina-woo-quotes' ), 'error' );
			return;
		}

		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		$added   = 0;
		$skipped = 0;

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$result = IWQ_Session::add_item(
				$cart_item['product_id'],
				$cart_item['quantity'],
				$cart_item['variation_id'],
				isset( $cart_item['variation'] ) ? $cart_item['variation'] : array()
			);

			if ( is_wp_error( $result ) ) {
				++$skipped;
				continue;
			}

			++$added;
		}

		if ( $added && iwq_option_enabled( 'empty_cart_after_transfer' ) ) {
			WC()->cart->empty_cart();
		}

		if ( $added ) {
			wc_add_notice(
				sprintf(
					/* translators: %s: número de productos. */
					_n(
						'Hemos añadido %s producto a tu solicitud de presupuesto.',
						'Hemos añadido %s productos a tu solicitud de presupuesto.',
						$added,
						'imagina-woo-quotes'
					),
					number_format_i18n( $added )
				),
				'success'
			);
		}

		if ( $skipped ) {
			wc_add_notice(
				sprintf(
					/* translators: %s: número de productos. */
					_n(
						'%s producto no admite presupuesto y se ha quedado en el carrito.',
						'%s productos no admiten presupuesto y se han quedado en el carrito.',
						$skipped,
						'imagina-woo-quotes'
					),
					number_format_i18n( $skipped )
				),
				'notice'
			);
		}

		$page_id = (int) iwq_get_option( 'quote_page_id' );

		if ( $page_id && $added ) {
			wp_safe_redirect( get_permalink( $page_id ) );
			exit;
		}
	}

	/* ---------------------------------------------------------------------
	 * Botones
	 * ------------------------------------------------------------------ */

	/**
	 * Pinta el botón en la ficha de producto.
	 *
	 * @return void
	 */
	public function render_single_button() {
		global $product;

		if ( ! iwq_option_enabled( 'show_on_product', true ) ) {
			return;
		}

		$this->output_button( $product, 'single' );
	}

	/**
	 * Pinta el botón cuando el producto no tiene formulario de compra.
	 *
	 * Los productos sin precio o fuera de stock no imprimen
	 * `woocommerce_after_add_to_cart_form`, así que sin este respaldo el
	 * botón desaparecería justo donde más falta hace.
	 *
	 * @return void
	 */
	public function render_single_button_fallback() {
		global $product;

		if ( ! $product instanceof WC_Product || $product->is_purchasable() ) {
			return;
		}

		$this->render_single_button();
	}

	/**
	 * Añade el botón al bloque de compra si el hook clásico no lo pintó.
	 *
	 * @param string $content HTML del bloque ya renderizado.
	 * @param array  $block   Bloque analizado.
	 * @return string
	 */
	public function append_to_add_to_cart_block( $content, $block ) {
		global $product;

		if ( ! iwq_option_enabled( 'show_on_product', true ) || ! $product instanceof WC_Product ) {
			return $content;
		}

		if ( isset( self::$rendered_single[ $product->get_id() ] ) ) {
			return $content;
		}

		$button = $this->get_button_html( $product, 'single' );

		if ( ! $button ) {
			return $content;
		}

		return 'before_add_to_cart' === iwq_get_option( 'button_position_single', 'after_add_to_cart' )
			? $button . $content
			: $content . $button;
	}

	/**
	 * Pinta el botón en el catálogo.
	 *
	 * @return void
	 */
	public function render_loop_button() {
		global $product;

		if ( ! iwq_option_enabled( 'show_on_shop', true ) ) {
			return;
		}

		$this->output_button( $product, 'loop' );
	}

	/**
	 * Pinta el botón que convierte el carrito en solicitud.
	 *
	 * @return void
	 */
	public function render_cart_button() {
		if ( ! iwq_option_enabled( 'show_on_cart' ) || WC()->cart->is_empty() ) {
			return;
		}

		self::$needs_assets = true;

		iwq_get_template( 'quote/cart-button.php' );
	}

	/**
	 * Añade el botón de presupuesto después del bloque Carrito.
	 *
	 * Se coloca fuera de la raíz del bloque para que React, al hidratar el
	 * carrito, no lo elimine.
	 *
	 * @param string $content HTML del bloque.
	 * @param array  $block   Bloque analizado.
	 * @return string
	 */
	public function append_cart_button_to_block( $content, $block ) {
		if ( ! iwq_option_enabled( 'show_on_cart' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return $content;
		}

		self::$needs_assets = true;

		return $content . '<div class="iwq iwq-cart-actions">' . iwq_get_template( 'quote/cart-button.php', array(), true ) . '</div>';
	}

	/**
	 * Imprime el botón de presupuesto para un producto.
	 *
	 * @param WC_Product $product Producto.
	 * @param string     $context `single` o `loop`.
	 * @return void
	 */
	private function output_button( $product, $context ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- la plantilla escapa su salida.
		echo $this->get_button_html( $product, $context );
	}

	/**
	 * Devuelve el HTML del botón para un producto, o cadena vacía si no
	 * corresponde mostrarlo.
	 *
	 * @param WC_Product $product Producto.
	 * @param string     $context `single`, `loop` o `shortcode`.
	 * @return string
	 */
	public function get_button_html( $product, $context ) {
		if ( ! $product instanceof WC_Product || ! IWQ_Exclusions::is_quotable( $product ) ) {
			return '';
		}

		if ( 'single' === $context ) {
			if ( isset( self::$rendered_single[ $product->get_id() ] ) ) {
				return '';
			}

			self::$rendered_single[ $product->get_id() ] = true;
		}

		self::$needs_assets = true;

		// En un producto variable, la variación concreta la elige el usuario:
		// el front la lee del formulario al pulsar el botón.
		return iwq_get_template(
			'quote/add-to-quote-button.php',
			array(
				'product'     => $product,
				'context'     => $context,
				'in_list'     => IWQ_Session::has_product( $product->get_id() ),
				'is_variable' => $product->is_type( 'variable' ),
			),
			true
		);
	}

	/**
	 * Pinta el panel lateral con la lista.
	 *
	 * Se imprime vacío y lo rellena el front bajo demanda: así el HTML de
	 * cada página sigue siendo cacheable.
	 *
	 * @return void
	 */
	public function render_drawer() {
		if ( ! self::$needs_assets && ! self::is_quote_page() ) {
			return;
		}

		iwq_get_template( 'quote/drawer.php' );
	}

	/* ---------------------------------------------------------------------
	 * Ocultación de precio y compra
	 * ------------------------------------------------------------------ */

	/**
	 * Sustituye el precio por el texto configurado cuando toca ocultarlo.
	 *
	 * @param string     $html    HTML del precio.
	 * @param WC_Product $product Producto.
	 * @return string
	 */
	public function filter_price_html( $html, $product ) {
		if ( ! IWQ_Exclusions::should_hide_price( $product ) ) {
			return $html;
		}

		$text = iwq_get_option( 'hide_price_text', __( 'Precio bajo consulta', 'imagina-woo-quotes' ) );

		/**
		 * Filtra el texto que reemplaza al precio oculto.
		 *
		 * @param string     $text    Texto de reemplazo.
		 * @param WC_Product $product Producto.
		 */
		$text = apply_filters( 'iwq_hidden_price_text', $text, $product );

		return $text ? '<span class="iwq-price-hidden">' . esc_html( $text ) . '</span>' : '';
	}

	/**
	 * Marca el producto como no comprable para esconder el botón de compra.
	 *
	 * Usar `is_purchasable` en lugar de ocultar el botón por CSS impide que
	 * alguien añada el producto al carrito por POST directo.
	 *
	 * @param bool       $purchasable Valor actual.
	 * @param WC_Product $product     Producto.
	 * @return bool
	 */
	public function filter_is_purchasable( $purchasable, $product ) {
		if ( IWQ_Exclusions::should_hide_add_to_cart( $product ) ) {
			return false;
		}

		return $purchasable;
	}

	/* ---------------------------------------------------------------------
	 * Utilidades
	 * ------------------------------------------------------------------ */

	/**
	 * Indica si la petición actual necesita los assets del plugin.
	 *
	 * @return bool
	 */
	public static function needs_assets() {
		return self::$needs_assets;
	}

	/**
	 * Fuerza la carga de los assets desde otro contexto (shortcode, bloque).
	 *
	 * @return void
	 */
	public static function require_assets() {
		self::$needs_assets = true;
	}

	/**
	 * Indica si estamos en la página de la lista de presupuesto.
	 *
	 * @return bool
	 */
	public static function is_quote_page() {
		$page_id = (int) iwq_get_option( 'quote_page_id' );

		return $page_id && is_page( $page_id );
	}
}
