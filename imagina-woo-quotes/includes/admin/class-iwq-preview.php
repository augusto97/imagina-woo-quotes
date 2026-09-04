<?php
/**
 * Vista previa de emails y PDF en el administrador.
 *
 * Muestra cada email exactamente como lo recibe su destinatario, con su
 * asunto, remitente y adjuntos, sobre un presupuesto real o uno de ejemplo,
 * y permite enviarlo a una dirección de prueba.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Preview
 */
class IWQ_Preview {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_iwq_preview_email', array( $this, 'ajax_preview_email' ) );
		add_action( 'wp_ajax_iwq_preview_meta', array( $this, 'ajax_preview_meta' ) );
		add_action( 'wp_ajax_iwq_preview_pdf', array( $this, 'ajax_preview_pdf' ) );
		add_action( 'wp_ajax_iwq_send_test_email', array( $this, 'ajax_send_test' ) );
		add_action( 'wp_ajax_iwq_create_sample', array( $this, 'ajax_create_sample' ) );
	}

	/**
	 * Emails del plugin con su destinatario.
	 *
	 * @return array<string,array{title:string,to:string,class:string}>
	 */
	public static function get_emails() {
		$list = array();

		foreach ( WC()->mailer()->get_emails() as $class_name => $email ) {
			if ( 0 !== strpos( $email->id, 'iwq_' ) ) {
				continue;
			}

			$list[ $email->id ] = array(
				'title' => $email->get_title(),
				'to'    => $email->goes_to_admin() ? 'admin' : 'customer',
				'class' => $class_name,
			);
		}

		return $list;
	}

	/**
	 * Presupuestos recientes para el desplegable.
	 *
	 * @return array<int,string>
	 */
	public static function get_recent_quotes() {
		$orders = wc_get_orders(
			array(
				'status'  => iwq_get_quote_statuses(),
				'limit'   => 30,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		$choices = array();

		foreach ( $orders as $order ) {
			$choices[ $order->get_id() ] = sprintf(
				'#%1$s · %2$s · %3$s · %4$s',
				$order->get_order_number(),
				$order->get_formatted_billing_full_name() ? $order->get_formatted_billing_full_name() : __( 'Sin nombre', 'imagina-woo-quotes' ),
				iwq_get_status_label( $order->get_status() ),
				wp_strip_all_tags( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) )
			);
		}

		return $choices;
	}

	/**
	 * Comprueba permisos y nonce de las peticiones.
	 *
	 * @return void
	 */
	private function authorize() {
		check_ajax_referer( 'iwq_preview', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permiso para ver esto.', 'imagina-woo-quotes' ), 403 );
		}
	}

	/**
	 * Devuelve el email pedido, ya cargado con el pedido.
	 *
	 * @return array{0:IWQ_Email_Base,1:WC_Order}|null
	 */
	private function load_email() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- comprobado en authorize().
		$email_id = isset( $_REQUEST['email'] ) ? sanitize_key( $_REQUEST['email'] ) : '';
		$order_id = isset( $_REQUEST['order'] ) ? absint( $_REQUEST['order'] ) : 0;
		// phpcs:enable

		$emails = self::get_emails();
		$order  = $order_id ? wc_get_order( $order_id ) : null;

		if ( ! isset( $emails[ $email_id ] ) || ! $order || ! iwq_is_quote( $order ) ) {
			return null;
		}

		$email = WC()->mailer()->get_emails()[ $emails[ $email_id ]['class'] ];

		if ( ! $email instanceof IWQ_Email_Base || ! $email->prepare( $order ) ) {
			return null;
		}

		return array( $email, $order );
	}

	/**
	 * AJAX: el HTML (o texto plano) del email, para el iframe.
	 *
	 * @return void
	 */
	public function ajax_preview_email() {
		$this->authorize();

		$loaded = $this->load_email();

		if ( ! $loaded ) {
			wp_die( esc_html__( 'Elige un email y un presupuesto.', 'imagina-woo-quotes' ) );
		}

		list( $email ) = $loaded;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- comprobado en authorize().
		$plain = isset( $_GET['format'] ) && 'plain' === $_GET['format'];

		nocache_headers();

		if ( $plain ) {
			header( 'Content-Type: text/plain; charset=' . get_bloginfo( 'charset' ) );
			echo $email->get_content_plain(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- texto plano tal como se envía.
			exit;
		}

		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
		// Mismo proceso que al enviar: CSS en línea.
		echo $email->style_inline( $email->get_content_html() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML del email tal como se envía.
		exit;
	}

	/**
	 * AJAX: asunto, destinatarios y adjuntos del email.
	 *
	 * @return void
	 */
	public function ajax_preview_meta() {
		$this->authorize();

		$loaded = $this->load_email();

		if ( ! $loaded ) {
			wp_send_json_error( array( 'message' => __( 'Elige un email y un presupuesto.', 'imagina-woo-quotes' ) ) );
		}

		list( $email, $order ) = $loaded;

		$attachments = array();

		foreach ( $email->get_attachments() as $path ) {
			$attachments[] = array(
				'name' => basename( $path ),
				'size' => file_exists( $path ) ? size_format( filesize( $path ) ) : __( 'no generado', 'imagina-woo-quotes' ),
			);
		}

		wp_send_json_success(
			array(
				'subject'     => $email->get_subject(),
				'from'        => sprintf( '%s <%s>', $email->get_from_name(), $email->get_from_address() ),
				'to'          => $email->get_recipient() ? $email->get_recipient() : __( '(sin destinatario: el pedido no tiene email)', 'imagina-woo-quotes' ),
				'enabled'     => $email->is_enabled(),
				'attachments' => $attachments,
				'pdf_url'     => IWQ_PDF::is_available() ? add_query_arg( array( 'action' => 'iwq_preview_pdf', 'order' => $order->get_id(), 'nonce' => wp_create_nonce( 'iwq_preview' ) ), admin_url( 'admin-ajax.php' ) ) : '',
				'status'      => iwq_get_status_label( $order->get_status() ),
			)
		);
	}

	/**
	 * AJAX: el PDF del presupuesto, regenerado, para el visor.
	 *
	 * @return void
	 */
	public function ajax_preview_pdf() {
		$this->authorize();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- comprobado en authorize().
		$order = isset( $_GET['order'] ) ? wc_get_order( absint( $_GET['order'] ) ) : null;

		if ( ! $order || ! iwq_is_quote( $order ) ) {
			wp_die( esc_html__( 'Ese presupuesto no existe.', 'imagina-woo-quotes' ), 404 );
		}

		$path = IWQ_PDF::get_or_generate( $order->get_id(), true );

		if ( ! $path ) {
			wp_die( esc_html__( 'No se pudo generar el PDF. Revisa el registro de WooCommerce → Estado → Registros.', 'imagina-woo-quotes' ), 500 );
		}

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="' . basename( $path ) . '"' );
		header( 'Content-Length: ' . filesize( $path ) );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	/**
	 * AJAX: envía el email a una dirección de prueba.
	 *
	 * @return void
	 */
	public function ajax_send_test() {
		$this->authorize();

		$loaded = $this->load_email();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- comprobado en authorize().
		$to = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';

		if ( ! $loaded || ! is_email( $to ) ) {
			wp_send_json_error( array( 'message' => __( 'Indica una dirección válida.', 'imagina-woo-quotes' ) ) );
		}

		list( $email ) = $loaded;

		$sent = $email->send( $to, '[' . __( 'Prueba', 'imagina-woo-quotes' ) . '] ' . $email->get_subject(), $email->get_content(), $email->get_headers(), $email->get_attachments() );

		if ( ! $sent ) {
			wp_send_json_error( array( 'message' => __( 'WordPress no pudo enviarlo. Revisa la configuración de correo del servidor o un plugin SMTP.', 'imagina-woo-quotes' ) ) );
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %s: dirección de email. */
					__( 'Enviado a %s con el mismo asunto, contenido y adjuntos que recibiría el destinatario.', 'imagina-woo-quotes' ),
					$to
				),
			)
		);
	}

	/**
	 * AJAX: crea un presupuesto de ejemplo para poder previsualizar.
	 *
	 * @return void
	 */
	public function ajax_create_sample() {
		$this->authorize();

		try {
			$this->create_sample();
		} catch ( \Throwable $e ) {
			wp_send_json_error(
				array(
					/* translators: %s: mensaje de error. */
					'message' => sprintf( __( 'No se pudo crear el ejemplo: %s', 'imagina-woo-quotes' ), $e->getMessage() ),
				)
			);
		}
	}

	/**
	 * Crea el presupuesto de ejemplo y responde con su ID y etiqueta.
	 *
	 * @return void
	 */
	private function create_sample() {
		$candidates = wc_get_products(
			array(
				'status'       => 'publish',
				'limit'        => 20,
				'type'         => array( 'simple', 'variable' ),
				'stock_status' => 'instock',
			)
		);

		// Solo productos con precio: un ejemplo a cero no enseña nada.
		$products = array();

		foreach ( $candidates as $candidate ) {
			if ( '' !== $candidate->get_price() && (float) $candidate->get_price() > 0 ) {
				$products[] = $candidate;
			}

			if ( 2 === count( $products ) ) {
				break;
			}
		}

		if ( ! $products ) {
			wp_send_json_error( array( 'message' => __( 'No hay productos publicados con los que crear el ejemplo.', 'imagina-woo-quotes' ) ) );
		}

		$order = wc_create_order( array( 'created_via' => 'imagina-quotes-sample' ) );

		foreach ( $products as $product ) {
			if ( $product->is_type( 'variable' ) ) {
				$children = $product->get_children();
				$product  = $children ? wc_get_product( $children[0] ) : null;
			}

			if ( $product ) {
				$order->add_product( $product, 2 );
			}
		}

		$order->set_billing_first_name( __( 'Cliente', 'imagina-woo-quotes' ) );
		$order->set_billing_last_name( __( 'de ejemplo', 'imagina-woo-quotes' ) );
		$order->set_billing_email( get_option( 'admin_email' ) );
		$order->set_billing_phone( '600 000 000' );
		$order->set_billing_company( get_bloginfo( 'name' ) );
		$order->update_meta_data( IWQ_Quote::META_IS_QUOTE, 'yes' );
		$order->update_meta_data( IWQ_Quote::META_PRICES_VISIBLE, 'yes' );
		$order->update_meta_data( '_iwq_sample', 'yes' );
		$order->update_meta_data( IWQ_Quote::META_FORM_DATA, array( 'message' => __( 'Este es un presupuesto de ejemplo creado desde la vista previa. Puedes borrarlo cuando quieras.', 'imagina-woo-quotes' ) ) );
		$order->calculate_totals( true );
		$order->set_status( 'iwq-new' );
		$order->save();

		$quote = new IWQ_Quote( $order );
		$quote->snapshot_list_prices();
		$order->save();

		// Con un 10 % de descuento y enviado, para que se vean los precios
		// tachados, la validez y los botones de respuesta.
		foreach ( $order->get_items() as $item ) {
			$item->set_total( (float) $item->get_total() * 0.9 );
			$item->save();
		}

		$order->calculate_totals( true );
		$order->save();
		$quote->send( false );

		$quote->add_negotiation_entry( array( 'author' => 'customer', 'offer' => round( (float) $order->get_total() * 0.85, 2 ), 'message' => __( '¿Podríais ajustar un poco más el precio? Somos clientes habituales.', 'imagina-woo-quotes' ) ) );

		wp_send_json_success(
			array(
				'id'    => $order->get_id(),
				'label' => sprintf( '#%s · %s', $order->get_order_number(), __( 'Cliente de ejemplo', 'imagina-woo-quotes' ) ),
			)
		);
	}

	/**
	 * Pinta la pestaña.
	 *
	 * @return void
	 */
	public static function render() {
		iwq_get_template(
			'admin/preview.php',
			array(
				'emails' => self::get_emails(),
				'quotes' => self::get_recent_quotes(),
				'nonce'  => wp_create_nonce( 'iwq_preview' ),
			)
		);
	}
}
