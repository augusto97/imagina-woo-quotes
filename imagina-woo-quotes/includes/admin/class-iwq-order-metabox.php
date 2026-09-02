<?php
/**
 * Panel del presupuesto dentro de la pantalla del pedido.
 *
 * Los precios se editan con el editor de líneas nativo de WooCommerce: aquí
 * solo añadimos lo que es propio del presupuesto (vencimiento, plantilla de
 * PDF, negociación y el botón de enviar).
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Order_Metabox
 */
class IWQ_Order_Metabox {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ), 20, 2 );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save' ), 20, 2 );
		add_action( 'admin_post_iwq_admin_offer', array( $this, 'handle_admin_offer' ) );
	}

	/**
	 * Registra el panel, compatible con HPOS y con el editor clásico.
	 *
	 * @param string          $screen_id Pantalla actual.
	 * @param WC_Order|WP_Post $object   Objeto de la pantalla.
	 * @return void
	 */
	public function register( $screen_id, $object = null ) {
		$order = $object instanceof WC_Order ? $object : wc_get_order( is_object( $object ) ? $object->ID : 0 );

		if ( ! $order || ! iwq_is_quote( $order ) ) {
			return;
		}

		add_meta_box(
			'iwq-quote',
			__( 'Presupuesto', 'imagina-woo-quotes' ),
			array( $this, 'render' ),
			$screen_id,
			'side',
			'high'
		);

		add_meta_box(
			'iwq-request-data',
			__( 'Datos de la solicitud', 'imagina-woo-quotes' ),
			array( $this, 'render_request_data' ),
			$screen_id,
			'normal',
			'high'
		);
	}

	/**
	 * Pinta las respuestas del formulario y los adjuntos.
	 *
	 * @param WC_Order|WP_Post $object Objeto de la pantalla.
	 * @return void
	 */
	public function render_request_data( $object ) {
		$order = $object instanceof WC_Order ? $object : wc_get_order( $object->ID );
		$quote = iwq_get_quote( $order );

		if ( ! $quote ) {
			return;
		}

		iwq_get_template(
			'admin/order-request-data.php',
			array(
				'order'     => $order,
				'form_data' => $quote->get_form_data(),
				'fields'    => iwq_get_all_form_fields(),
			)
		);
	}

	/**
	 * Pinta el panel.
	 *
	 * @param WC_Order|WP_Post $object Objeto de la pantalla.
	 * @return void
	 */
	public function render( $object ) {
		$order = $object instanceof WC_Order ? $object : wc_get_order( $object->ID );
		$quote = iwq_get_quote( $order );

		if ( ! $quote ) {
			return;
		}

		wp_nonce_field( 'iwq_save_quote', 'iwq_quote_nonce' );

		iwq_get_template(
			'admin/order-metabox.php',
			array(
				'order' => $order,
				'quote' => $quote,
			)
		);
	}

	/**
	 * Guarda los datos propios del presupuesto.
	 *
	 * @param int      $order_id ID del pedido.
	 * @param WC_Order $order    Pedido.
	 * @return void
	 */
	public function save( $order_id, $order = null ) {
		if ( ! isset( $_POST['iwq_quote_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['iwq_quote_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'iwq_save_quote' ) || ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}

		$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );

		if ( ! $order || ! iwq_is_quote( $order ) ) {
			return;
		}

		$quote = new IWQ_Quote( $order );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- comprobado arriba.
		if ( isset( $_POST['iwq_expiry_date'] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_POST['iwq_expiry_date'] ) );

			// Una fecha vacía significa «sin vencimiento».
			$quote->set_expiry_date( $raw ? strtotime( $raw . ' 23:59:59' ) : 0 );
		}

		if ( isset( $_POST['iwq_pdf_template_id'] ) ) {
			$order->update_meta_data( '_iwq_pdf_template_id', absint( $_POST['iwq_pdf_template_id'] ) );
		}
		// phpcs:enable

		$order->save();
	}

	/**
	 * Procesa la respuesta del administrador a una contraoferta.
	 *
	 * @return void
	 */
	public function handle_admin_offer() {
		check_admin_referer( 'iwq_admin_offer' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( esc_html__( 'No tienes permiso para hacer esto.', 'imagina-woo-quotes' ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- comprobado por check_admin_referer.
		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$message  = isset( $_POST['iwq_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['iwq_message'] ) ) : '';
		// phpcs:enable

		$order = wc_get_order( $order_id );

		if ( $order && iwq_is_quote( $order ) && $message ) {
			$quote = new IWQ_Quote( $order );

			$quote->add_negotiation_entry(
				array(
					'author'  => 'admin',
					'message' => $message,
				)
			);
		}

		wp_safe_redirect( $order ? $order->get_edit_order_url() : admin_url() );
		exit;
	}
}
