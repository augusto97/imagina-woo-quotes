<?php
/**
 * Convierte una solicitud del cliente en un pedido de WooCommerce, y procesa
 * las respuestas del cliente (aceptar, rechazar, contraofertar).
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Request_Handler
 */
class IWQ_Request_Handler {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wc_ajax_iwq_submit_request', array( $this, 'ajax_submit' ) );
		add_action( 'wc_ajax_nopriv_iwq_submit_request', array( $this, 'ajax_submit' ) );
		add_action( 'wc_ajax_iwq_counter_offer', array( $this, 'ajax_counter_offer' ) );
		add_action( 'wc_ajax_nopriv_iwq_counter_offer', array( $this, 'ajax_counter_offer' ) );

		// Las respuestas por enlace del email llegan como GET.
		add_action( 'template_redirect', array( $this, 'handle_quote_action' ) );
	}

	/* ---------------------------------------------------------------------
	 * Envío de la solicitud
	 * ------------------------------------------------------------------ */

	/**
	 * AJAX: procesa el envío del formulario.
	 *
	 * @return void
	 */
	public function ajax_submit() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'iwq_frontend' ) ) {
			wp_send_json_error( array( 'message' => __( 'La sesión caducó. Recarga la página e inténtalo de nuevo.', 'imagina-woo-quotes' ) ), 403 );
		}

		if ( IWQ_Session::is_empty() ) {
			wp_send_json_error( array( 'message' => __( 'Tu lista de presupuesto está vacía.', 'imagina-woo-quotes' ) ), 400 );
		}

		if ( ! $this->passes_rate_limit() ) {
			wp_send_json_error( array( 'message' => __( 'Has enviado varias solicitudes seguidas. Espera unos minutos antes de volver a intentarlo.', 'imagina-woo-quotes' ) ), 429 );
		}

		$captcha = IWQ_Recaptcha::verify();

		if ( is_wp_error( $captcha ) ) {
			wp_send_json_error( array( 'message' => $captcha->get_error_message() ), 400 );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- comprobado arriba.
		$input     = isset( $_POST['iwq_field'] ) && is_array( $_POST['iwq_field'] ) ? $_POST['iwq_field'] : array();
		$validator = new IWQ_Form_Validator();

		if ( ! $validator->validate( $input, $_FILES ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Revisa los campos marcados.', 'imagina-woo-quotes' ),
					'errors'  => $validator->get_errors(),
				),
				422
			);
		}

		$order = $this->create_quote_order( $validator->get_values() );

		if ( is_wp_error( $order ) ) {
			wp_send_json_error( array( 'message' => $order->get_error_message() ), 500 );
		}

		IWQ_Session::clear();

		wp_send_json_success(
			array(
				'order_id' => $order->get_id(),
				'message'  => iwq_get_option( 'success_message', __( '¡Gracias! Hemos recibido tu solicitud y te responderemos en breve.', 'imagina-woo-quotes' ) ),
				'redirect' => $this->get_redirect_url( $order ),
			)
		);
	}

	/**
	 * Limita cuántas solicitudes puede enviar una misma IP.
	 *
	 * Sin esto el formulario es un vector cómodo para inundar la tienda de
	 * pedidos basura.
	 *
	 * @return bool
	 */
	private function passes_rate_limit() {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		$limit = (int) iwq_get_option( 'rate_limit', 5 );

		if ( $limit < 1 ) {
			return true;
		}

		$key   = 'iwq_rl_' . md5( WC_Geolocation::get_ip_address() );
		$count = (int) get_transient( $key );

		if ( $count >= $limit ) {
			return false;
		}

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );

		return true;
	}

	/**
	 * Crea el pedido que representa el presupuesto solicitado.
	 *
	 * @param array $form_data Datos ya validados del formulario.
	 * @return WC_Order|WP_Error
	 */
	public function create_quote_order( $form_data ) {
		try {
			$order = wc_create_order(
				array(
					'customer_id' => get_current_user_id(),
					'created_via' => 'imagina-quotes',
				)
			);

			if ( is_wp_error( $order ) ) {
				return $order;
			}

			$this->add_items_to_order( $order );
			$this->apply_form_data_to_order( $order, $form_data );

			$order->update_meta_data( IWQ_Quote::META_IS_QUOTE, 'yes' );
			$order->update_meta_data( IWQ_Quote::META_FORM_DATA, $form_data );
			$order->set_currency( get_woocommerce_currency() );

			// Los precios los pone el administrador al preparar el
			// presupuesto: la solicitud nace sin importe.
			$order->calculate_totals( false );
			$order->set_status( 'iwq-new', __( 'Solicitud de presupuesto recibida.', 'imagina-woo-quotes' ) );
			$order->save();

			$quote = new IWQ_Quote( $order );
			$quote->snapshot_list_prices();
			$order->save();

			$this->bump_request_counters( $order );

			/**
			 * Se dispara cuando se registra una nueva solicitud de presupuesto.
			 *
			 * @param int      $order_id  ID del pedido creado.
			 * @param array    $form_data Datos del formulario.
			 * @param WC_Order $order     Pedido.
			 */
			do_action( 'iwq_request_created', $order->get_id(), $form_data, $order );

			return $order;
		} catch ( Exception $e ) {
			wc_get_logger()->error(
				'No se pudo crear el presupuesto: ' . $e->getMessage(),
				array( 'source' => 'imagina-woo-quotes' )
			);

			return new WP_Error( 'iwq_order_failed', __( 'No pudimos registrar tu solicitud. Inténtalo de nuevo en unos minutos.', 'imagina-woo-quotes' ) );
		}
	}

	/**
	 * Vuelca la lista de presupuesto en las líneas del pedido.
	 *
	 * @param WC_Order $order Pedido.
	 * @return void
	 */
	private function add_items_to_order( $order ) {
		foreach ( IWQ_Session::get_items() as $item ) {
			$product_id = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
			$product    = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			$args = array(
				'variation' => isset( $item['variation'] ) ? $item['variation'] : array(),
			);

			// El importe arranca en cero: es una solicitud, no una venta.
			$args['subtotal'] = 0;
			$args['total']    = 0;

			/**
			 * Filtra los argumentos de una línea antes de añadirla al pedido.
			 *
			 * @param array      $args    Argumentos para `add_product()`.
			 * @param array      $item    Línea de la lista de presupuesto.
			 * @param WC_Product $product Producto.
			 */
			$args = apply_filters( 'iwq_order_item_args', $args, $item, $product );

			$order->add_product( $product, (int) $item['quantity'], $args );
		}
	}

	/**
	 * Copia los datos del formulario a las propiedades del pedido.
	 *
	 * Los campos con `connect_to` acaban en los campos nativos de WooCommerce;
	 * el resto se guarda como metadato del formulario.
	 *
	 * @param WC_Order $order     Pedido.
	 * @param array    $form_data Datos validados.
	 * @return void
	 */
	private function apply_form_data_to_order( $order, $form_data ) {
		foreach ( iwq_get_form_fields() as $field ) {
			if ( empty( $field['connect_to'] ) || ! isset( $form_data[ $field['id'] ] ) ) {
				continue;
			}

			$value  = $form_data[ $field['id'] ];
			$setter = 'set_' . $field['connect_to'];

			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}

			if ( is_callable( array( $order, $setter ) ) ) {
				$order->$setter( $value );
			}
		}

		// Si no hay campo enlazado al email pero el usuario está registrado,
		// usamos el suyo: sin email no podemos responderle.
		if ( ! $order->get_billing_email() && is_user_logged_in() ) {
			$order->set_billing_email( wp_get_current_user()->user_email );
		}

		$order->set_customer_ip_address( WC_Geolocation::get_ip_address() );
		$order->set_customer_user_agent( wc_get_user_agent() );
	}

	/**
	 * Incrementa el contador de solicitudes de cada producto del pedido.
	 *
	 * Da una señal de demanda que no ofrece ningún otro plugin del sector:
	 * qué productos se piden mucho y se venden poco.
	 *
	 * @param WC_Order $order Pedido.
	 * @return void
	 */
	private function bump_request_counters( $order ) {
		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();

			if ( ! $product_id ) {
				continue;
			}

			$count = (int) get_post_meta( $product_id, '_iwq_request_count', true );
			update_post_meta( $product_id, '_iwq_request_count', $count + 1 );
		}
	}

	/**
	 * URL a la que redirigir tras enviar la solicitud.
	 *
	 * @param WC_Order $order Pedido creado.
	 * @return string
	 */
	private function get_redirect_url( $order ) {
		$page_id = (int) iwq_get_option( 'thankyou_page_id' );
		$url     = $page_id ? get_permalink( $page_id ) : '';

		if ( ! $url && is_user_logged_in() ) {
			$url = $order->get_view_order_url();
		}

		/**
		 * Filtra la URL de redirección tras enviar una solicitud.
		 *
		 * @param string   $url   URL de destino, vacía para no redirigir.
		 * @param WC_Order $order Pedido creado.
		 */
		return apply_filters( 'iwq_after_submit_redirect', $url, $order );
	}

	/* ---------------------------------------------------------------------
	 * Respuestas del cliente
	 * ------------------------------------------------------------------ */

	/**
	 * Procesa los enlaces de aceptar y rechazar que van en el email y el PDF.
	 *
	 * @return void
	 */
	public function handle_quote_action() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- se valida con un HMAC propio.
		if ( empty( $_GET['iwq_quote'] ) || empty( $_GET['iwq_action'] ) ) {
			return;
		}

		$order_id = absint( $_GET['iwq_quote'] );
		$action   = sanitize_key( $_GET['iwq_action'] );
		$key      = isset( $_GET['iwq_key'] ) ? sanitize_text_field( wp_unslash( $_GET['iwq_key'] ) ) : '';
		$token    = isset( $_GET['iwq_token'] ) ? sanitize_text_field( wp_unslash( $_GET['iwq_token'] ) ) : '';
		// phpcs:enable

		$order = wc_get_order( $order_id );

		// La clave del pedido y el HMAC se comprueban juntos: el enlace solo
		// sirve para este pedido y esta acción concreta.
		if ( ! $order || ! hash_equals( $order->get_order_key(), $key ) || ! iwq_verify_quote_action_token( $order, $action, $token ) ) {
			wc_add_notice( __( 'Ese enlace no es válido o ha caducado.', 'imagina-woo-quotes' ), 'error' );
			return;
		}

		$quote = new IWQ_Quote( $order );

		if ( ! $quote->is_actionable() ) {
			wc_add_notice(
				$quote->is_expired()
					? __( 'Este presupuesto ha vencido. Escríbenos si quieres que lo actualicemos.', 'imagina-woo-quotes' )
					: __( 'Este presupuesto ya no admite cambios.', 'imagina-woo-quotes' ),
				'notice'
			);
			return;
		}

		if ( 'accept' === $action ) {
			$quote->accept();
			$this->redirect_after_accept( $quote );
		}

		if ( 'reject' === $action ) {
			$quote->reject();
			wc_add_notice( __( 'Hemos registrado tu respuesta. Gracias por avisarnos.', 'imagina-woo-quotes' ), 'success' );
		}
	}

	/**
	 * Lleva al cliente al pago tras aceptar, si así está configurado.
	 *
	 * @param IWQ_Quote $quote Presupuesto aceptado.
	 * @return void
	 */
	private function redirect_after_accept( $quote ) {
		wc_add_notice( __( 'Has aceptado el presupuesto. ¡Gracias!', 'imagina-woo-quotes' ), 'success' );

		if ( ! iwq_option_enabled( 'redirect_to_payment', true ) ) {
			return;
		}

		$url = $quote->get_order()->get_checkout_payment_url();

		/**
		 * Filtra la URL a la que se envía al cliente tras aceptar.
		 *
		 * @param string    $url   URL de pago.
		 * @param IWQ_Quote $quote Presupuesto.
		 */
		$url = apply_filters( 'iwq_after_accept_redirect', $url, $quote );

		if ( $url ) {
			wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * AJAX: registra una contraoferta del cliente.
	 *
	 * @return void
	 */
	public function ajax_counter_offer() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'iwq_frontend' ) ) {
			wp_send_json_error( array( 'message' => __( 'La sesión caducó. Recarga la página.', 'imagina-woo-quotes' ) ), 403 );
		}

		if ( ! iwq_option_enabled( 'allow_counter_offers', true ) ) {
			wp_send_json_error( array( 'message' => __( 'Las contraofertas no están habilitadas.', 'imagina-woo-quotes' ) ), 400 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- comprobado arriba.
		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$offer    = isset( $_POST['offer'] ) ? (float) wc_clean( wp_unslash( $_POST['offer'] ) ) : 0;
		$message  = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		// phpcs:enable

		$order = wc_get_order( $order_id );

		if ( ! $order || ! $this->user_owns_order( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'No tienes permiso sobre este presupuesto.', 'imagina-woo-quotes' ) ), 403 );
		}

		$quote = new IWQ_Quote( $order );

		if ( ! $quote->add_counter_offer( $offer, $message ) ) {
			wp_send_json_error( array( 'message' => __( 'No se pudo registrar tu contraoferta.', 'imagina-woo-quotes' ) ), 400 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Hemos enviado tu contraoferta. Te responderemos en breve.', 'imagina-woo-quotes' ),
			)
		);
	}

	/**
	 * Comprueba que el usuario actual es el titular del pedido.
	 *
	 * @param WC_Order $order Pedido.
	 * @return bool
	 */
	private function user_owns_order( $order ) {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		return is_user_logged_in() && get_current_user_id() === $order->get_customer_id();
	}
}
