<?php
/**
 * Registro de los emails del plugin en WooCommerce.
 *
 * Extender `WC_Email` en vez de enviar con `wp_mail()` hace que cada email
 * aparezca en WooCommerce → Ajustes → Emails, con su asunto, encabezado,
 * destinatarios y plantilla editables desde el admin.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Emails
 */
class IWQ_Emails {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_email_classes', array( $this, 'register' ) );
		add_filter( 'woocommerce_template_directory', array( $this, 'template_directory' ), 10, 2 );
		add_filter( 'woocommerce_email_styles', array( $this, 'email_styles' ), 20, 2 );
		// Solo en texto plano: en HTML las partes de la plantilla ya lo pintan.
		add_action( 'woocommerce_email_order_details', array( $this, 'render_quote_details' ), 5, 4 );
		add_filter( 'woocommerce_email_attachments', array( $this, 'attach_pdf' ), 10, 4 );

		// En solicitudes con precios ocultos, la tabla de WooCommerce no debe
		// enseñar subtotales ni totales.
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'maybe_hide_totals' ), 10, 2 );
		add_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'maybe_hide_line_subtotal' ), 10, 3 );

		// Los emails se disparan desde las acciones de la máquina de estados.
		add_action( 'iwq_request_created', array( $this, 'trigger_new_request' ) );
		add_action( 'iwq_quote_sent', array( $this, 'trigger_quote_sent' ) );
		add_action( 'iwq_quote_accepted', array( $this, 'trigger_status_change' ) );
		add_action( 'iwq_quote_rejected', array( $this, 'trigger_status_change' ) );
		add_action( 'iwq_counter_offer_received', array( $this, 'trigger_counter_offer' ) );
	}

	/**
	 * Registra las clases de email.
	 *
	 * @param array $emails Clases ya registradas.
	 * @return array
	 */
	public function register( $emails ) {
		$emails['IWQ_Email_New_Request']      = new IWQ_Email_New_Request();
		$emails['IWQ_Email_Request_Received'] = new IWQ_Email_Request_Received();
		$emails['IWQ_Email_Quote_Sent']       = new IWQ_Email_Quote_Sent();
		$emails['IWQ_Email_Quote_Status']     = new IWQ_Email_Quote_Status();
		$emails['IWQ_Email_Counter_Offer']    = new IWQ_Email_Counter_Offer();
		$emails['IWQ_Email_Reminder']         = new IWQ_Email_Reminder();

		return $emails;
	}

	/**
	 * Dice a WooCommerce dónde buscar nuestras plantillas de email.
	 *
	 * @param string $directory Directorio actual.
	 * @param string $template  Ruta de la plantilla.
	 * @return string
	 */
	public function template_directory( $directory, $template ) {
		return 0 === strpos( $template, 'emails/iwq-' ) ? 'imagina-woo-quotes' : $directory;
	}

	/**
	 * Sustituye el CSS de WooCommerce por el del diseño elegido en nuestros
	 * emails. Con el diseño «Como WooCommerce» se conservan ambos.
	 *
	 * @param string   $css   CSS actual.
	 * @param WC_Email $email Email en curso.
	 * @return string
	 */
	public function email_styles( $css, $email = null ) {
		if ( ! $email || 0 !== strpos( $email->id, 'iwq_' ) ) {
			return $css;
		}

		$style = IWQ_Email_Styles::get_current();

		return ( 'woocommerce' === $style ? $css : '' ) . IWQ_Email_Styles::get_css( $style );
	}

	/**
	 * Añade al email de texto plano los datos del formulario.
	 *
	 * @param WC_Order $order         Pedido.
	 * @param bool     $sent_to_admin Si el email va al administrador.
	 * @param bool     $plain_text    Si es la versión de texto plano.
	 * @param WC_Email $email         Email que se está construyendo.
	 * @return void
	 */
	public function render_quote_details( $order, $sent_to_admin, $plain_text, $email ) {
		if ( ! $plain_text || ! iwq_is_quote( $order ) || 0 !== strpos( $email->id, 'iwq_' ) ) {
			return;
		}

		$quote = iwq_get_quote( $order );

		if ( ! $quote ) {
			return;
		}

		$data = $quote->get_form_data();

		if ( empty( $data ) ) {
			return;
		}

		iwq_get_template(
			'emails/plain/form-data.php',
			array(
				'form_data'     => $data,
				'order'         => $order,
				'sent_to_admin' => $sent_to_admin,
			)
		);
	}

	/**
	 * Quita las filas de totales si el cliente no debe ver precios.
	 *
	 * @param array    $total_rows Filas de totales.
	 * @param WC_Order $order      Pedido.
	 * @return array
	 */
	public function maybe_hide_totals( $total_rows, $order ) {
		if ( ! $order instanceof WC_Order || ! iwq_is_quote( $order ) ) {
			return $total_rows;
		}

		$quote = iwq_get_quote( $order );

		return $quote && ! $quote->prices_visible() ? array() : $total_rows;
	}

	/**
	 * Quita el subtotal de línea si el cliente no debe ver precios.
	 *
	 * @param string        $subtotal HTML del subtotal.
	 * @param WC_Order_Item $item     Línea.
	 * @param WC_Order      $order    Pedido.
	 * @return string
	 */
	public function maybe_hide_line_subtotal( $subtotal, $item, $order ) {
		if ( ! $order instanceof WC_Order || ! iwq_is_quote( $order ) ) {
			return $subtotal;
		}

		$quote = iwq_get_quote( $order );

		return $quote && ! $quote->prices_visible() ? '' : $subtotal;
	}

	/**
	 * Adjunta el PDF del presupuesto al email correspondiente.
	 *
	 * @param array    $attachments Adjuntos actuales.
	 * @param string   $email_id    Identificador del email.
	 * @param WC_Order $order       Objeto asociado al email.
	 * @param WC_Email $email       Email que se está construyendo.
	 * @return array
	 */
	public function attach_pdf( $attachments, $email_id, $order, $email = null ) {
		/**
		 * Filtra los emails que llevan el PDF adjunto.
		 *
		 * En la solicitud nueva y su confirmación el documento aún no tiene
		 * precios: sirve como resguardo de qué se pidió.
		 *
		 * @param string[] $with_pdf Identificadores de email.
		 */
		$with_pdf = apply_filters(
			'iwq_emails_with_pdf',
			array( 'iwq_new_request', 'iwq_request_received', 'iwq_quote_sent', 'iwq_reminder' )
		);

		if ( ! in_array( $email_id, $with_pdf, true ) ) {
			return $attachments;
		}

		if ( ! iwq_option_enabled( 'pdf_attach_to_email', true ) || ! $order instanceof WC_Order ) {
			return $attachments;
		}

		$path = IWQ_PDF::get_or_generate( $order->get_id() );

		if ( $path && is_readable( $path ) ) {
			$attachments[] = $path;
		}

		return $attachments;
	}

	/* ---------------------------------------------------------------------
	 * Disparadores
	 * ------------------------------------------------------------------ */

	/**
	 * Avisa al administrador y confirma al cliente la nueva solicitud.
	 *
	 * @param int $order_id ID del pedido.
	 * @return void
	 */
	public function trigger_new_request( $order_id ) {
		$this->send( 'IWQ_Email_New_Request', $order_id );
		$this->send( 'IWQ_Email_Request_Received', $order_id );
	}

	/**
	 * Envía al cliente el presupuesto preparado.
	 *
	 * @param int $order_id ID del pedido.
	 * @return void
	 */
	public function trigger_quote_sent( $order_id ) {
		$this->send( 'IWQ_Email_Quote_Sent', $order_id );
	}

	/**
	 * Avisa al administrador de la respuesta del cliente.
	 *
	 * @param int $order_id ID del pedido.
	 * @return void
	 */
	public function trigger_status_change( $order_id ) {
		$this->send( 'IWQ_Email_Quote_Status', $order_id );
	}

	/**
	 * Avisa al administrador de una contraoferta.
	 *
	 * @param int $order_id ID del pedido.
	 * @return void
	 */
	public function trigger_counter_offer( $order_id ) {
		$this->send( 'IWQ_Email_Counter_Offer', $order_id );
	}

	/**
	 * Dispara un email por su clase.
	 *
	 * @param string $class_name Nombre de la clase de email.
	 * @param int    $order_id   ID del pedido.
	 * @return void
	 */
	private function send( $class_name, $order_id ) {
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();

		if ( isset( $emails[ $class_name ] ) ) {
			$emails[ $class_name ]->trigger( $order_id );
		}
	}
}
