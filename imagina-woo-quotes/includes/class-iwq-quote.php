<?php
/**
 * Objeto presupuesto: envuelve un WC_Order y le añade la máquina de estados.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Quote
 */
class IWQ_Quote {

	const META_IS_QUOTE     = '_iwq_is_quote';
	const META_EXPIRY       = '_iwq_expiry_date';
	const META_SENT_AT      = '_iwq_sent_at';
	const META_RESPONDED_AT = '_iwq_responded_at';
	const META_FORM_DATA    = '_iwq_form_data';
	const META_NEGOTIATION  = '_iwq_negotiation';
	const META_REMINDERS    = '_iwq_reminders_sent';
	const META_PDF_FILE     = '_iwq_pdf_file';
	const META_LIST_PRICES  = '_iwq_list_prices';

	/**
	 * Pedido subyacente.
	 *
	 * @var WC_Order
	 */
	private $order;

	/**
	 * Constructor.
	 *
	 * @param WC_Order|int $order Pedido o su ID.
	 * @throws InvalidArgumentException Si el pedido no existe.
	 */
	public function __construct( $order ) {
		$order = is_numeric( $order ) ? wc_get_order( $order ) : $order;

		if ( ! $order instanceof WC_Order ) {
			throw new InvalidArgumentException( 'El pedido indicado no existe.' );
		}

		$this->order = $order;
	}

	/**
	 * Devuelve el pedido subyacente.
	 *
	 * @return WC_Order
	 */
	public function get_order() {
		return $this->order;
	}

	/**
	 * ID del pedido.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->order->get_id();
	}

	/* ---------------------------------------------------------------------
	 * Transiciones de estado
	 * ------------------------------------------------------------------ */

	/**
	 * Marca el presupuesto como enviado al cliente.
	 *
	 * Fija la fecha de vencimiento, recalcula totales y dispara el email.
	 *
	 * @param bool $notify Si false, no se envía el email al cliente.
	 * @return bool True si la transición se aplicó.
	 */
	public function send( $notify = true ) {
		if ( ! $this->can_transition_to( 'iwq-pending' ) ) {
			return false;
		}

		$this->order->calculate_totals( false );
		$this->set_expiry_from_settings();
		$this->order->update_meta_data( self::META_SENT_AT, time() );
		$this->order->delete_meta_data( self::META_REMINDERS );

		$this->order->set_status(
			'iwq-pending',
			__( 'Presupuesto enviado al cliente.', 'imagina-woo-quotes' )
		);
		$this->order->save();

		if ( $notify ) {
			/**
			 * Se dispara al enviar un presupuesto al cliente.
			 *
			 * Lo escucha la clase de email `IWQ_Email_Quote_Sent`.
			 *
			 * @param int       $order_id ID del pedido.
			 * @param IWQ_Quote $quote    Presupuesto.
			 */
			do_action( 'iwq_quote_sent', $this->get_id(), $this );
		}

		return true;
	}

	/**
	 * El cliente acepta el presupuesto.
	 *
	 * @param string $context `customer` o `admin`, para la nota del pedido.
	 * @return bool
	 */
	public function accept( $context = 'customer' ) {
		if ( ! $this->can_transition_to( 'iwq-accepted' ) ) {
			return false;
		}

		$this->order->update_meta_data( self::META_RESPONDED_AT, time() );
		$this->order->set_status(
			'iwq-accepted',
			'admin' === $context
				? __( 'Presupuesto aceptado desde el administrador.', 'imagina-woo-quotes' )
				: __( 'El cliente aceptó el presupuesto.', 'imagina-woo-quotes' )
		);
		$this->order->save();

		/**
		 * Se dispara cuando un presupuesto es aceptado.
		 *
		 * @param int       $order_id ID del pedido.
		 * @param IWQ_Quote $quote    Presupuesto.
		 */
		do_action( 'iwq_quote_accepted', $this->get_id(), $this );

		return true;
	}

	/**
	 * El cliente rechaza el presupuesto.
	 *
	 * @param string $reason  Motivo opcional indicado por el cliente.
	 * @param string $context `customer` o `admin`.
	 * @return bool
	 */
	public function reject( $reason = '', $context = 'customer' ) {
		if ( ! $this->can_transition_to( 'iwq-rejected' ) ) {
			return false;
		}

		$note = 'admin' === $context
			? __( 'Presupuesto rechazado desde el administrador.', 'imagina-woo-quotes' )
			: __( 'El cliente rechazó el presupuesto.', 'imagina-woo-quotes' );

		if ( $reason ) {
			$note .= ' ' . sprintf(
				/* translators: %s: motivo indicado por el cliente. */
				__( 'Motivo: %s', 'imagina-woo-quotes' ),
				$reason
			);
		}

		$this->order->update_meta_data( self::META_RESPONDED_AT, time() );
		$this->order->set_status( 'iwq-rejected', $note );
		$this->order->save();

		/**
		 * Se dispara cuando un presupuesto es rechazado.
		 *
		 * @param int       $order_id ID del pedido.
		 * @param IWQ_Quote $quote    Presupuesto.
		 * @param string    $reason   Motivo del rechazo.
		 */
		do_action( 'iwq_quote_rejected', $this->get_id(), $this, $reason );

		return true;
	}

	/**
	 * Marca el presupuesto como vencido.
	 *
	 * @return bool
	 */
	public function expire() {
		if ( 'iwq-pending' !== $this->order->get_status() ) {
			return false;
		}

		$this->order->set_status(
			'iwq-expired',
			__( 'El presupuesto venció sin respuesta del cliente.', 'imagina-woo-quotes' )
		);
		$this->order->save();

		/**
		 * Se dispara cuando un presupuesto vence.
		 *
		 * @param int       $order_id ID del pedido.
		 * @param IWQ_Quote $quote    Presupuesto.
		 */
		do_action( 'iwq_quote_expired', $this->get_id(), $this );

		return true;
	}

	/**
	 * Comprueba si una transición está permitida desde el estado actual.
	 *
	 * @param string $to Estado destino, sin el prefijo `wc-`.
	 * @return bool
	 */
	public function can_transition_to( $to ) {
		$map = array(
			'iwq-pending'  => array( 'iwq-new', 'iwq-expired', 'iwq-rejected' ),
			'iwq-accepted' => array( 'iwq-pending' ),
			'iwq-rejected' => array( 'iwq-pending' ),
			'iwq-expired'  => array( 'iwq-pending' ),
		);

		$allowed = isset( $map[ $to ] ) ? $map[ $to ] : array();

		/**
		 * Filtra los estados de origen permitidos para una transición.
		 *
		 * @param string[]  $allowed Estados de origen válidos.
		 * @param string    $to      Estado destino.
		 * @param IWQ_Quote $quote   Presupuesto.
		 */
		$allowed = apply_filters( 'iwq_allowed_transitions', $allowed, $to, $this );

		return in_array( $this->order->get_status(), $allowed, true );
	}

	/* ---------------------------------------------------------------------
	 * Vencimiento
	 * ------------------------------------------------------------------ */

	/**
	 * Fija la fecha de vencimiento a partir de los ajustes.
	 *
	 * Si el pedido ya tiene una fecha puesta a mano por el administrador,
	 * se respeta.
	 *
	 * @return void
	 */
	public function set_expiry_from_settings() {
		if ( ! iwq_option_enabled( 'auto_expire', true ) ) {
			return;
		}

		if ( $this->order->get_meta( self::META_EXPIRY ) ) {
			return;
		}

		$days = max( 1, (int) iwq_get_option( 'expiry_days', 7 ) );

		$this->set_expiry_date( time() + ( $days * DAY_IN_SECONDS ) );
	}

	/**
	 * Fija la fecha de vencimiento.
	 *
	 * @param int $timestamp Marca de tiempo Unix.
	 * @return void
	 */
	public function set_expiry_date( $timestamp ) {
		$this->order->update_meta_data( self::META_EXPIRY, (int) $timestamp );
	}

	/**
	 * Devuelve la fecha de vencimiento.
	 *
	 * @return int Marca de tiempo, o 0 si no vence.
	 */
	public function get_expiry_date() {
		return (int) $this->order->get_meta( self::META_EXPIRY );
	}

	/**
	 * Indica si el presupuesto ya pasó su fecha de vencimiento.
	 *
	 * @return bool
	 */
	public function is_expired() {
		$expiry = $this->get_expiry_date();

		return $expiry > 0 && $expiry < time();
	}

	/**
	 * Días que faltan para el vencimiento.
	 *
	 * @return int|null Null si el presupuesto no vence.
	 */
	public function get_days_to_expiry() {
		$expiry = $this->get_expiry_date();

		if ( ! $expiry ) {
			return null;
		}

		return (int) ceil( ( $expiry - time() ) / DAY_IN_SECONDS );
	}

	/* ---------------------------------------------------------------------
	 * Negociación
	 * ------------------------------------------------------------------ */

	/**
	 * Devuelve el hilo de negociación.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_negotiation_thread() {
		$thread = $this->order->get_meta( self::META_NEGOTIATION );

		return is_array( $thread ) ? $thread : array();
	}

	/**
	 * Añade una entrada al hilo de negociación.
	 *
	 * @param array $entry {
	 *     Entrada del hilo.
	 *
	 *     @type string $author  `customer` o `admin`.
	 *     @type string $message Mensaje en texto plano.
	 *     @type float  $offer   Importe propuesto, o null si no hay contraoferta.
	 * }
	 * @return void
	 */
	public function add_negotiation_entry( $entry ) {
		$thread = $this->get_negotiation_thread();

		$thread[] = wp_parse_args(
			$entry,
			array(
				'author'  => 'customer',
				'user_id' => get_current_user_id(),
				'message' => '',
				'offer'   => null,
				'date'    => time(),
			)
		);

		$this->order->update_meta_data( self::META_NEGOTIATION, $thread );
		$this->order->save();
	}

	/**
	 * Registra una contraoferta del cliente.
	 *
	 * No cambia el estado: el presupuesto sigue pendiente hasta que el
	 * administrador responda con una nueva versión o el cliente acepte.
	 *
	 * @param float  $offer   Importe propuesto por el cliente.
	 * @param string $message Mensaje que acompaña la contraoferta.
	 * @return bool
	 */
	public function add_counter_offer( $offer, $message = '' ) {
		if ( 'iwq-pending' !== $this->order->get_status() ) {
			return false;
		}

		$offer = (float) $offer;

		if ( $offer <= 0 ) {
			return false;
		}

		$this->add_negotiation_entry(
			array(
				'author'  => 'customer',
				'message' => $message,
				'offer'   => $offer,
			)
		);

		$this->order->add_order_note(
			sprintf(
				/* translators: %s: importe propuesto por el cliente. */
				__( 'El cliente envió una contraoferta de %s.', 'imagina-woo-quotes' ),
				wp_strip_all_tags( wc_price( $offer, array( 'currency' => $this->order->get_currency() ) ) )
			)
		);

		/**
		 * Se dispara cuando el cliente envía una contraoferta.
		 *
		 * @param int       $order_id ID del pedido.
		 * @param float     $offer    Importe propuesto.
		 * @param string    $message  Mensaje del cliente.
		 * @param IWQ_Quote $quote    Presupuesto.
		 */
		do_action( 'iwq_counter_offer_received', $this->get_id(), $offer, $message, $this );

		return true;
	}

	/**
	 * Devuelve la última contraoferta pendiente de respuesta.
	 *
	 * @return array|null
	 */
	public function get_latest_counter_offer() {
		$thread = array_reverse( $this->get_negotiation_thread() );

		foreach ( $thread as $entry ) {
			if ( 'customer' === $entry['author'] && ! empty( $entry['offer'] ) ) {
				return $entry;
			}
		}

		return null;
	}

	/* ---------------------------------------------------------------------
	 * Datos del formulario y precios
	 * ------------------------------------------------------------------ */

	/**
	 * Devuelve los datos enviados en el formulario de solicitud.
	 *
	 * @return array<string,mixed>
	 */
	public function get_form_data() {
		$data = $this->order->get_meta( self::META_FORM_DATA );

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Guarda los datos del formulario de solicitud.
	 *
	 * @param array $data Datos ya validados y saneados.
	 * @return void
	 */
	public function set_form_data( $data ) {
		$this->order->update_meta_data( self::META_FORM_DATA, $data );
	}

	/**
	 * Guarda los precios de catálogo del momento de la solicitud.
	 *
	 * Sirven para mostrar el precio original tachado cuando el presupuesto
	 * ofrece un descuento.
	 *
	 * @return void
	 */
	public function snapshot_list_prices() {
		$prices = array();

		foreach ( $this->order->get_items() as $item_id => $item ) {
			$product = $item->get_product();

			if ( $product ) {
				$prices[ $item_id ] = (float) wc_get_price_to_display( $product );
			}
		}

		$this->order->update_meta_data( self::META_LIST_PRICES, $prices );
	}

	/**
	 * Devuelve el precio de catálogo guardado para una línea.
	 *
	 * @param int $item_id ID de la línea del pedido.
	 * @return float|null
	 */
	public function get_list_price( $item_id ) {
		$prices = $this->order->get_meta( self::META_LIST_PRICES );

		return isset( $prices[ $item_id ] ) ? (float) $prices[ $item_id ] : null;
	}

	/* ---------------------------------------------------------------------
	 * Utilidades
	 * ------------------------------------------------------------------ */

	/**
	 * URL para que el cliente vea el presupuesto.
	 *
	 * @return string
	 */
	public function get_view_url() {
		return $this->order->get_view_order_url();
	}

	/**
	 * URL de aceptación firmada.
	 *
	 * @return string
	 */
	public function get_accept_url() {
		return iwq_get_quote_action_url( $this->order, 'accept' );
	}

	/**
	 * URL de rechazo firmada.
	 *
	 * @return string
	 */
	public function get_reject_url() {
		return iwq_get_quote_action_url( $this->order, 'reject' );
	}

	/**
	 * Indica si el cliente todavía puede responder al presupuesto.
	 *
	 * @return bool
	 */
	public function is_actionable() {
		return 'iwq-pending' === $this->order->get_status() && ! $this->is_expired();
	}

	/**
	 * Registra que se envió un recordatorio, para no repetirlo.
	 *
	 * @param string $type Identificador del recordatorio.
	 * @return void
	 */
	public function mark_reminder_sent( $type ) {
		$sent = $this->order->get_meta( self::META_REMINDERS );
		$sent = is_array( $sent ) ? $sent : array();

		$sent[ $type ] = time();

		$this->order->update_meta_data( self::META_REMINDERS, $sent );
		$this->order->save();
	}

	/**
	 * Comprueba si ya se envió un recordatorio concreto.
	 *
	 * @param string $type Identificador del recordatorio.
	 * @return bool
	 */
	public function reminder_was_sent( $type ) {
		$sent = $this->order->get_meta( self::META_REMINDERS );

		return is_array( $sent ) && isset( $sent[ $type ] );
	}
}
