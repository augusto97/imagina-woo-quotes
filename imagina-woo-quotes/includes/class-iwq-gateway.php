<?php
/**
 * Pasarela que convierte el pedido en una solicitud de presupuesto.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	return;
}

/**
 * Class IWQ_Gateway
 */
class IWQ_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'iwq_quote';
		$this->method_title       = __( 'Solicitar presupuesto', 'imagina-woo-quotes' );
		$this->method_description = __( 'Permite al cliente terminar el checkout como solicitud de presupuesto en vez de como compra. El pedido queda pendiente de valoración.', 'imagina-woo-quotes' );
		$this->has_fields         = false;

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Ajustes de la pasarela.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array(
				'title'   => __( 'Activar', 'imagina-woo-quotes' ),
				'type'    => 'checkbox',
				'label'   => __( 'Ofrecer «Solicitar presupuesto» en el checkout', 'imagina-woo-quotes' ),
				'default' => 'no',
			),
			'title'       => array(
				'title'       => __( 'Título', 'imagina-woo-quotes' ),
				'type'        => 'text',
				'description' => __( 'Lo que ve el cliente al elegir la forma de pago.', 'imagina-woo-quotes' ),
				'default'     => __( 'Solicitar presupuesto', 'imagina-woo-quotes' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'    => __( 'Descripción', 'imagina-woo-quotes' ),
				'type'     => 'textarea',
				'default'  => __( 'Enviaremos tu pedido como solicitud de presupuesto y te responderemos con los precios.', 'imagina-woo-quotes' ),
				'desc_tip' => true,
			),
		);
	}

	/**
	 * Convierte el pedido en presupuesto y vacía el carrito.
	 *
	 * @param int $order_id ID del pedido.
	 * @return array<string,string>
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return array( 'result' => 'failure' );
		}

		$order->update_meta_data( IWQ_Quote::META_IS_QUOTE, 'yes' );
		// En el checkout el cliente ya vio los precios.
		$order->update_meta_data( IWQ_Quote::META_PRICES_VISIBLE, 'yes' );

		// Los importes del checkout se conservan como punto de partida; el
		// administrador los ajusta al valorar.
		$order->calculate_totals( true );
		$order->set_status( 'iwq-new', __( 'Solicitud de presupuesto creada desde el checkout.', 'imagina-woo-quotes' ) );
		$order->save();

		$quote = new IWQ_Quote( $order );
		$quote->snapshot_list_prices();
		$order->save();

		WC()->cart->empty_cart();

		/**
		 * Se dispara al crear un presupuesto desde el checkout.
		 *
		 * @param int      $order_id ID del pedido.
		 * @param array    $data     Datos del formulario (vacío en este flujo).
		 * @param WC_Order $order    Pedido.
		 */
		do_action( 'iwq_request_created', $order->get_id(), array(), $order );

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * La pasarela solo se ofrece si hay algún producto presupuestable.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' !== $this->enabled || ! IWQ_Exclusions::user_can_request() ) {
			return false;
		}

		return parent::is_available();
	}
}
