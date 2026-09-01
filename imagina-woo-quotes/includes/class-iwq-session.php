<?php
/**
 * Lista de presupuesto del visitante.
 *
 * Decisión de diseño clave: no se abre la sesión de WooCommerce hasta que el
 * usuario añade el primer producto. Abrirla antes pondría una cookie a todo
 * visitante anónimo, lo que invalida la caché de página completa del sitio
 * entero y es el motivo por el que los plugins de este tipo tienen fama de
 * ralentizar las tiendas.
 *
 * Para los usuarios registrados la lista también se guarda en su user meta,
 * de modo que sobrevive al cierre de sesión y se comparte entre dispositivos.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Session
 */
class IWQ_Session {

	const SESSION_KEY = 'iwq_quote_list';
	const USER_META   = '_iwq_quote_list';

	/**
	 * Caché en memoria de la lista para no releerla en cada llamada.
	 *
	 * @var array<string,array>|null
	 */
	private static $cache = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Endpoints AJAX ligeros de WooCommerce (`?wc-ajax=...`): no cargan
		// admin-ajax.php ni el admin, así que responden mucho más rápido. Un
		// solo hook por acción atiende a usuarios registrados y anónimos.
		add_action( 'wc_ajax_iwq_add_item', array( $this, 'ajax_add_item' ) );
		add_action( 'wc_ajax_iwq_remove_item', array( $this, 'ajax_remove_item' ) );
		add_action( 'wc_ajax_iwq_update_item', array( $this, 'ajax_update_item' ) );
		add_action( 'wc_ajax_iwq_get_list', array( $this, 'ajax_get_list' ) );
		add_action( 'wc_ajax_iwq_clear_list', array( $this, 'ajax_clear_list' ) );

		// Al iniciar sesión, fusionamos la lista de invitado con la guardada.
		add_action( 'wp_login', array( $this, 'merge_on_login' ), 10, 2 );
	}

	/* ---------------------------------------------------------------------
	 * Lectura y escritura
	 * ------------------------------------------------------------------ */

	/**
	 * Devuelve los productos de la lista.
	 *
	 * @return array<string,array>
	 */
	public static function get_items() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$items = array();

		if ( is_user_logged_in() ) {
			$stored = get_user_meta( get_current_user_id(), self::USER_META, true );
			$items  = is_array( $stored ) ? $stored : array();
		}

		// La sesión tiene prioridad: refleja lo que el usuario hizo en esta visita.
		if ( self::session_available() ) {
			$session_items = WC()->session->get( self::SESSION_KEY );

			if ( is_array( $session_items ) ) {
				$items = $session_items;
			}
		}

		self::$cache = self::filter_valid_items( $items );

		return self::$cache;
	}

	/**
	 * Guarda la lista en la sesión y, si procede, en el perfil del usuario.
	 *
	 * @param array<string,array> $items Lista de productos.
	 * @return void
	 */
	public static function set_items( $items ) {
		self::$cache = $items;

		if ( self::session_available() ) {
			WC()->session->set( self::SESSION_KEY, $items );
		}

		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), self::USER_META, $items );
		}
	}

	/**
	 * Comprueba si la sesión de WooCommerce está disponible.
	 *
	 * @return bool
	 */
	private static function session_available() {
		return function_exists( 'WC' ) && isset( WC()->session ) && WC()->session;
	}

	/**
	 * Descarta líneas cuyo producto ya no existe o no es comprable.
	 *
	 * Evita fatales al renderizar la lista tras borrar un producto.
	 *
	 * @param array $items Lista sin filtrar.
	 * @return array<string,array>
	 */
	private static function filter_valid_items( $items ) {
		$valid = array();

		foreach ( (array) $items as $key => $item ) {
			if ( empty( $item['product_id'] ) ) {
				continue;
			}

			$product_id = ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
			$product    = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			// Los productos despublicados desaparecen de la lista, salvo para
			// quien puede editarlos (útil al preparar el catálogo).
			if ( 'publish' !== $product->get_status() && ! current_user_can( 'edit_products' ) ) {
				continue;
			}

			$valid[ $key ] = $item;
		}

		return $valid;
	}

	/* ---------------------------------------------------------------------
	 * Operaciones sobre la lista
	 * ------------------------------------------------------------------ */

	/**
	 * Genera la clave única de una línea.
	 *
	 * Se incluyen los atributos de variación y los datos extra para que dos
	 * configuraciones distintas del mismo producto convivan como líneas
	 * separadas, igual que hace el carrito de WooCommerce.
	 *
	 * @param int   $product_id   ID del producto.
	 * @param int   $variation_id ID de la variación.
	 * @param array $variation    Atributos elegidos.
	 * @return string
	 */
	public static function generate_key( $product_id, $variation_id = 0, $variation = array() ) {
		return md5( wp_json_encode( array( (int) $product_id, (int) $variation_id, $variation ) ) );
	}

	/**
	 * Añade un producto a la lista.
	 *
	 * @param int   $product_id   ID del producto.
	 * @param int   $quantity     Cantidad.
	 * @param int   $variation_id ID de la variación.
	 * @param array $variation    Atributos elegidos.
	 * @return string|WP_Error Clave de la línea o error.
	 */
	public static function add_item( $product_id, $quantity = 1, $variation_id = 0, $variation = array() ) {
		$product_id = absint( $product_id );
		$product    = wc_get_product( $variation_id ? $variation_id : $product_id );

		if ( ! $product ) {
			return new WP_Error( 'iwq_invalid_product', __( 'Ese producto no existe.', 'imagina-woo-quotes' ) );
		}

		if ( ! IWQ_Exclusions::is_quotable( $product ) ) {
			return new WP_Error( 'iwq_not_quotable', __( 'Ese producto no admite solicitud de presupuesto.', 'imagina-woo-quotes' ) );
		}

		$quantity = self::sanitize_quantity( $quantity, $product );
		$key      = self::generate_key( $product_id, $variation_id, $variation );
		$items    = self::get_items();

		if ( isset( $items[ $key ] ) ) {
			$items[ $key ]['quantity'] = self::sanitize_quantity( $items[ $key ]['quantity'] + $quantity, $product );
		} else {
			$items[ $key ] = array(
				'key'          => $key,
				'product_id'   => $product_id,
				'variation_id' => absint( $variation_id ),
				'variation'    => $variation,
				'quantity'     => $quantity,
				'added_at'     => time(),
			);
		}

		/**
		 * Filtra una línea antes de guardarla en la lista.
		 *
		 * @param array      $item    Datos de la línea.
		 * @param WC_Product $product Producto.
		 */
		$items[ $key ] = apply_filters( 'iwq_add_item_data', $items[ $key ], $product );

		self::set_items( $items );

		/**
		 * Se dispara al añadir un producto a la lista de presupuesto.
		 *
		 * @param string $key        Clave de la línea.
		 * @param int    $product_id ID del producto.
		 * @param int    $quantity   Cantidad añadida.
		 */
		do_action( 'iwq_item_added', $key, $product_id, $quantity );

		return $key;
	}

	/**
	 * Ajusta la cantidad a los límites configurados y al stock.
	 *
	 * @param int        $quantity Cantidad pedida.
	 * @param WC_Product $product  Producto.
	 * @return int
	 */
	private static function sanitize_quantity( $quantity, $product ) {
		$quantity = max( 1, (int) $quantity );
		$min      = (int) iwq_get_option( 'min_quantity', 0 );
		$max      = (int) iwq_get_option( 'max_quantity', 0 );

		if ( $min > 0 ) {
			$quantity = max( $min, $quantity );
		}

		if ( $max > 0 ) {
			$quantity = min( $max, $quantity );
		}

		/**
		 * Filtra la cantidad final de una línea.
		 *
		 * @param int        $quantity Cantidad ajustada.
		 * @param WC_Product $product  Producto.
		 */
		return (int) apply_filters( 'iwq_sanitize_quantity', $quantity, $product );
	}

	/**
	 * Quita una línea de la lista.
	 *
	 * @param string $key Clave de la línea.
	 * @return bool
	 */
	public static function remove_item( $key ) {
		$items = self::get_items();

		if ( ! isset( $items[ $key ] ) ) {
			return false;
		}

		unset( $items[ $key ] );
		self::set_items( $items );

		/**
		 * Se dispara al quitar una línea de la lista.
		 *
		 * @param string $key Clave de la línea.
		 */
		do_action( 'iwq_item_removed', $key );

		return true;
	}

	/**
	 * Cambia la cantidad de una línea.
	 *
	 * @param string $key      Clave de la línea.
	 * @param int    $quantity Nueva cantidad. Cero elimina la línea.
	 * @return bool
	 */
	public static function update_quantity( $key, $quantity ) {
		$items = self::get_items();

		if ( ! isset( $items[ $key ] ) ) {
			return false;
		}

		$quantity = (int) $quantity;

		if ( $quantity < 1 ) {
			return self::remove_item( $key );
		}

		$item    = $items[ $key ];
		$product = wc_get_product( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );

		if ( ! $product ) {
			return false;
		}

		$items[ $key ]['quantity'] = self::sanitize_quantity( $quantity, $product );
		self::set_items( $items );

		return true;
	}

	/**
	 * Vacía la lista.
	 *
	 * @return void
	 */
	public static function clear() {
		self::set_items( array() );

		/**
		 * Se dispara al vaciar la lista de presupuesto.
		 */
		do_action( 'iwq_list_cleared' );
	}

	/**
	 * Indica si un producto ya está en la lista.
	 *
	 * @param int $product_id ID del producto o de la variación.
	 * @return bool
	 */
	public static function has_product( $product_id ) {
		$product_id = absint( $product_id );

		foreach ( self::get_items() as $item ) {
			if ( absint( $item['product_id'] ) === $product_id || absint( $item['variation_id'] ) === $product_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Número de líneas de la lista.
	 *
	 * @return int
	 */
	public static function count() {
		return count( self::get_items() );
	}

	/**
	 * Suma de cantidades de la lista.
	 *
	 * @return int
	 */
	public static function get_total_quantity() {
		$total = 0;

		foreach ( self::get_items() as $item ) {
			$total += (int) $item['quantity'];
		}

		return $total;
	}

	/**
	 * Indica si la lista está vacía.
	 *
	 * @return bool
	 */
	public static function is_empty() {
		return 0 === self::count();
	}

	/* ---------------------------------------------------------------------
	 * Endpoints AJAX
	 * ------------------------------------------------------------------ */

	/**
	 * Comprueba el nonce de las peticiones AJAX.
	 *
	 * @return void
	 */
	private function check_nonce() {
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'iwq_frontend' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'La sesión caducó. Recarga la página e inténtalo de nuevo.', 'imagina-woo-quotes' ) ),
				403
			);
		}
	}

	/**
	 * AJAX: añadir producto.
	 *
	 * @return void
	 */
	public function ajax_add_item() {
		$this->check_nonce();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- comprobado arriba.
		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		$quantity     = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;
		$variation    = isset( $_POST['variation'] ) && is_array( $_POST['variation'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['variation'] ) )
			: array();
		// phpcs:enable

		$result = self::add_item( $product_id, $quantity, $variation_id, $variation );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success( $this->get_response_payload() );
	}

	/**
	 * AJAX: quitar producto.
	 *
	 * @return void
	 */
	public function ajax_remove_item() {
		$this->check_nonce();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- comprobado arriba.
		$key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';

		self::remove_item( $key );

		wp_send_json_success( $this->get_response_payload() );
	}

	/**
	 * AJAX: cambiar cantidad.
	 *
	 * @return void
	 */
	public function ajax_update_item() {
		$this->check_nonce();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- comprobado arriba.
		$key      = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;
		// phpcs:enable

		self::update_quantity( $key, $quantity );

		wp_send_json_success( $this->get_response_payload() );
	}

	/**
	 * AJAX: devolver la lista completa para pintar el panel lateral.
	 *
	 * @return void
	 */
	public function ajax_get_list() {
		$this->check_nonce();

		wp_send_json_success( $this->get_response_payload( true ) );
	}

	/**
	 * AJAX: vaciar la lista.
	 *
	 * @return void
	 */
	public function ajax_clear_list() {
		$this->check_nonce();

		self::clear();

		wp_send_json_success( $this->get_response_payload( true ) );
	}

	/**
	 * Construye la respuesta común de los endpoints.
	 *
	 * @param bool $with_html Si true incluye el HTML del panel lateral.
	 * @return array<string,mixed>
	 */
	private function get_response_payload( $with_html = false ) {
		$payload = array(
			'count'    => self::count(),
			'quantity' => self::get_total_quantity(),
			'ids'      => self::get_product_ids(),
		);

		if ( $with_html ) {
			$payload['html'] = iwq_get_template(
				'quote/drawer-content.php',
				array( 'items' => self::get_items() ),
				true
			);
		}

		return $payload;
	}

	/**
	 * IDs de los productos presentes en la lista.
	 *
	 * El front los usa para marcar los botones ya añadidos sin pedir nada
	 * más al servidor.
	 *
	 * @return int[]
	 */
	public static function get_product_ids() {
		$ids = array();

		foreach ( self::get_items() as $item ) {
			$ids[] = (int) $item['product_id'];

			if ( ! empty( $item['variation_id'] ) ) {
				$ids[] = (int) $item['variation_id'];
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/* ---------------------------------------------------------------------
	 * Persistencia entre sesiones
	 * ------------------------------------------------------------------ */

	/**
	 * Fusiona la lista de invitado con la guardada al iniciar sesión.
	 *
	 * @param string  $user_login Nombre de usuario.
	 * @param WP_User $user       Usuario que inicia sesión.
	 * @return void
	 */
	public function merge_on_login( $user_login, $user ) {
		if ( ! self::session_available() ) {
			return;
		}

		$guest_items = WC()->session->get( self::SESSION_KEY );

		if ( empty( $guest_items ) || ! is_array( $guest_items ) ) {
			return;
		}

		$stored = get_user_meta( $user->ID, self::USER_META, true );
		$stored = is_array( $stored ) ? $stored : array();

		// Las líneas de la visita actual pisan a las guardadas si coinciden.
		$merged = array_merge( $stored, $guest_items );

		update_user_meta( $user->ID, self::USER_META, $merged );
		WC()->session->set( self::SESSION_KEY, $merged );

		self::$cache = null;
	}
}
