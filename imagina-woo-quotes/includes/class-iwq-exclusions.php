<?php
/**
 * Motor de reglas: decide para qué productos y para qué usuarios se ofrece
 * el presupuesto, y dónde se ocultan precios y botón de compra.
 *
 * Todas las decisiones pasan por aquí para que el comportamiento sea
 * coherente entre el catálogo, la ficha de producto, el carrito y la API.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Exclusions
 */
class IWQ_Exclusions {

	const META_MODE = '_iwq_mode';

	/**
	 * Caché por petición de las decisiones ya calculadas.
	 *
	 * Evita repetir el cálculo en catálogos donde el mismo producto aparece
	 * varias veces (destacados, relacionados, etc.).
	 *
	 * @var array<string,bool>
	 */
	private static $cache = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'iwq_settings_saved', array( __CLASS__, 'flush_cache' ) );
	}

	/**
	 * Vacía la caché en memoria.
	 *
	 * @return void
	 */
	public static function flush_cache() {
		self::$cache = array();
	}

	/**
	 * Modo de presupuesto configurado para un producto concreto.
	 *
	 * Valores posibles:
	 *   - `inherit`  usa las reglas globales (por defecto).
	 *   - `enabled`  fuerza el botón de presupuesto.
	 *   - `disabled` nunca ofrece presupuesto para este producto.
	 *   - `only`     solo presupuesto: oculta precio y botón de compra.
	 *
	 * @param WC_Product $product Producto.
	 * @return string
	 */
	public static function get_product_mode( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return 'inherit';
		}

		// En una variación, la configuración vive en el producto padre.
		$id   = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
		$mode = get_post_meta( $id, self::META_MODE, true );

		return $mode ? $mode : 'inherit';
	}

	/**
	 * Indica si un producto admite solicitud de presupuesto.
	 *
	 * @param WC_Product|int $product Producto o su ID.
	 * @return bool
	 */
	public static function is_quotable( $product ) {
		$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;

		if ( ! $product instanceof WC_Product ) {
			return false;
		}

		$cache_key = 'quotable_' . $product->get_id() . '_' . get_current_user_id();

		if ( isset( self::$cache[ $cache_key ] ) ) {
			return self::$cache[ $cache_key ];
		}

		$result = self::calculate_is_quotable( $product );

		/**
		 * Filtra si un producto admite solicitud de presupuesto.
		 *
		 * @param bool       $result  Decisión calculada.
		 * @param WC_Product $product Producto.
		 */
		$result = (bool) apply_filters( 'iwq_is_quotable', $result, $product );

		self::$cache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * Calcula si un producto admite presupuesto, sin caché.
	 *
	 * @param WC_Product $product Producto.
	 * @return bool
	 */
	private static function calculate_is_quotable( $product ) {
		if ( ! iwq_option_enabled( 'enabled', true ) ) {
			return false;
		}

		if ( ! self::user_can_request() ) {
			return false;
		}

		$mode = self::get_product_mode( $product );

		// La configuración del propio producto manda sobre las reglas globales.
		if ( 'disabled' === $mode ) {
			return false;
		}

		if ( in_array( $mode, array( 'enabled', 'only' ), true ) ) {
			return true;
		}

		// Tipos de producto que no tienen sentido presupuestar.
		if ( $product->is_type( array( 'external', 'grouped' ) ) && ! iwq_option_enabled( 'allow_external_grouped' ) ) {
			return false;
		}

		if ( ! self::passes_taxonomy_rules( $product ) ) {
			return false;
		}

		if ( ! self::passes_stock_rule( $product ) ) {
			return false;
		}

		// Modo global: `all` para todo el catálogo, `rules` solo donde las
		// reglas de taxonomía coincidan.
		$scope = iwq_get_option( 'scope', 'all' );

		if ( 'rules' === $scope ) {
			return self::matches_include_rules( $product );
		}

		return true;
	}

	/**
	 * Comprueba las listas de inclusión y exclusión por taxonomía.
	 *
	 * @param WC_Product $product Producto.
	 * @return bool
	 */
	private static function passes_taxonomy_rules( $product ) {
		$parent_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();

		$excluded_products = (array) iwq_get_option( 'excluded_products', array() );

		if ( in_array( $parent_id, array_map( 'absint', $excluded_products ), true ) ) {
			return false;
		}

		$excluded_cats = (array) iwq_get_option( 'excluded_categories', array() );

		if ( $excluded_cats && self::has_term( $parent_id, 'product_cat', $excluded_cats ) ) {
			return false;
		}

		$excluded_tags = (array) iwq_get_option( 'excluded_tags', array() );

		if ( $excluded_tags && self::has_term( $parent_id, 'product_tag', $excluded_tags ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Comprueba si el producto entra en las listas de inclusión.
	 *
	 * Solo se usa cuando el ámbito global es `rules`.
	 *
	 * @param WC_Product $product Producto.
	 * @return bool
	 */
	private static function matches_include_rules( $product ) {
		$parent_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();

		$products = array_map( 'absint', (array) iwq_get_option( 'included_products', array() ) );

		if ( $products && in_array( $parent_id, $products, true ) ) {
			return true;
		}

		$cats = (array) iwq_get_option( 'included_categories', array() );

		if ( $cats && self::has_term( $parent_id, 'product_cat', $cats ) ) {
			return true;
		}

		$tags = (array) iwq_get_option( 'included_tags', array() );

		if ( $tags && self::has_term( $parent_id, 'product_tag', $tags ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Regla de stock: opcionalmente solo se presupuestan los agotados.
	 *
	 * @param WC_Product $product Producto.
	 * @return bool
	 */
	private static function passes_stock_rule( $product ) {
		$rule = iwq_get_option( 'stock_rule', 'any' );

		if ( 'out_of_stock' === $rule ) {
			return ! $product->is_in_stock();
		}

		if ( 'in_stock' === $rule ) {
			return $product->is_in_stock();
		}

		return true;
	}

	/**
	 * Comprueba si un producto pertenece a alguno de los términos dados.
	 *
	 * @param int      $product_id ID del producto.
	 * @param string   $taxonomy   Taxonomía.
	 * @param array    $terms      IDs de término.
	 * @return bool
	 */
	private static function has_term( $product_id, $taxonomy, $terms ) {
		$terms = array_map( 'absint', (array) $terms );

		return has_term( $terms, $taxonomy, $product_id );
	}

	/* ---------------------------------------------------------------------
	 * Reglas por usuario
	 * ------------------------------------------------------------------ */

	/**
	 * Indica si el usuario actual puede solicitar presupuestos.
	 *
	 * @return bool
	 */
	public static function user_can_request() {
		if ( ! is_user_logged_in() && ! iwq_option_enabled( 'allow_guests', true ) ) {
			return false;
		}

		$allowed_roles = (array) iwq_get_option( 'allowed_roles', array() );

		// Lista vacía significa "todos los usuarios".
		if ( empty( $allowed_roles ) ) {
			return true;
		}

		if ( ! is_user_logged_in() ) {
			return in_array( 'guest', $allowed_roles, true );
		}

		$user = wp_get_current_user();

		return (bool) array_intersect( $allowed_roles, (array) $user->roles );
	}

	/* ---------------------------------------------------------------------
	 * Ocultación de precio y botón de compra
	 * ------------------------------------------------------------------ */

	/**
	 * Indica si hay que ocultar el precio de un producto.
	 *
	 * @param WC_Product $product Producto.
	 * @return bool
	 */
	public static function should_hide_price( $product ) {
		if ( ! self::is_quotable( $product ) ) {
			return false;
		}

		if ( 'only' === self::get_product_mode( $product ) ) {
			return true;
		}

		if ( ! iwq_option_enabled( 'hide_price' ) ) {
			return false;
		}

		return self::applies_to_current_user( 'hide_price_roles' );
	}

	/**
	 * Indica si hay que ocultar el botón de añadir al carrito.
	 *
	 * @param WC_Product $product Producto.
	 * @return bool
	 */
	public static function should_hide_add_to_cart( $product ) {
		if ( ! self::is_quotable( $product ) ) {
			return false;
		}

		if ( 'only' === self::get_product_mode( $product ) ) {
			return true;
		}

		if ( ! iwq_option_enabled( 'hide_add_to_cart' ) ) {
			return false;
		}

		return self::applies_to_current_user( 'hide_add_to_cart_roles' );
	}

	/**
	 * Comprueba si una regla por rol aplica al usuario actual.
	 *
	 * @param string $option_key Opción que guarda los roles afectados.
	 * @return bool
	 */
	private static function applies_to_current_user( $option_key ) {
		$roles = (array) iwq_get_option( $option_key, array() );

		// Lista vacía significa "a todo el mundo".
		if ( empty( $roles ) ) {
			return true;
		}

		if ( ! is_user_logged_in() ) {
			return in_array( 'guest', $roles, true );
		}

		$user = wp_get_current_user();

		return (bool) array_intersect( $roles, (array) $user->roles );
	}
}
