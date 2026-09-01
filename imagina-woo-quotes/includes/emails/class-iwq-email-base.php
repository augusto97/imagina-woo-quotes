<?php
/**
 * Base común de los emails del plugin.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

/**
 * Class IWQ_Email_Base
 */
abstract class IWQ_Email_Base extends WC_Email {

	/**
	 * Presupuesto asociado al email.
	 *
	 * @var IWQ_Quote|null
	 */
	public $quote = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->customer_email = ! $this->is_for_admin();
		$this->template_base  = IWQ_TEMPLATE_PATH;

		parent::__construct();

		if ( $this->is_for_admin() ) {
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}
	}

	/**
	 * Indica si el email va dirigido al administrador.
	 *
	 * @return bool
	 */
	protected function is_for_admin() {
		return false;
	}

	/**
	 * Prepara el email para un pedido y lo envía.
	 *
	 * @param int $order_id ID del pedido.
	 * @return void
	 */
	public function trigger( $order_id ) {
		$this->setup_locale();

		$order = $order_id ? wc_get_order( $order_id ) : false;

		if ( $order instanceof WC_Order ) {
			$this->object                         = $order;
			$this->quote                          = iwq_get_quote( $order );
			$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{order_number}'] = $order->get_order_number();
			$this->placeholders['{customer_name}'] = $order->get_formatted_billing_full_name();
			$this->placeholders['{expiry_date}']  = $this->get_expiry_placeholder();

			if ( ! $this->is_for_admin() ) {
				$this->recipient = $order->get_billing_email();
			}
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * Valor legible de la fecha de vencimiento, para los marcadores.
	 *
	 * @return string
	 */
	protected function get_expiry_placeholder() {
		if ( ! $this->quote || ! $this->quote->get_expiry_date() ) {
			return '';
		}

		return date_i18n( get_option( 'date_format' ), $this->quote->get_expiry_date() );
	}

	/**
	 * Contenido HTML del email.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			$this->get_template_args( false ),
			'',
			$this->template_base
		);
	}

	/**
	 * Contenido en texto plano.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			$this->get_template_args( true ),
			'',
			$this->template_base
		);
	}

	/**
	 * Argumentos comunes de las plantillas.
	 *
	 * @param bool $plain_text Si es la versión de texto plano.
	 * @return array<string,mixed>
	 */
	protected function get_template_args( $plain_text ) {
		return array(
			'order'              => $this->object,
			'quote'              => $this->quote,
			'email_heading'      => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => $this->is_for_admin(),
			'plain_text'         => $plain_text,
			'email'              => $this,
		);
	}

	/**
	 * Añade el destinatario a los ajustes de los emails de administración.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		parent::init_form_fields();

		if ( ! $this->is_for_admin() ) {
			return;
		}

		$fields = array();

		foreach ( $this->form_fields as $key => $field ) {
			$fields[ $key ] = $field;

			if ( 'enabled' === $key ) {
				$fields['recipient'] = array(
					'title'       => __( 'Destinatarios', 'imagina-woo-quotes' ),
					'type'        => 'text',
					'description' => sprintf(
						/* translators: %s: dirección de email del administrador. */
						__( 'Direcciones separadas por comas. Por defecto: %s', 'imagina-woo-quotes' ),
						'<code>' . esc_html( get_option( 'admin_email' ) ) . '</code>'
					),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
				);
			}
		}

		$this->form_fields = $fields;
	}
}
