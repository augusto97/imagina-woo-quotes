<?php
/**
 * Email: recordatorio de vencimiento para el cliente.
 *
 * Devuelve solo el cuerpo; el envoltorio lo pone emails/layout.php según el
 * diseño elegido. Se puede sobreescribir copiándolo a
 * `tu-tema/imagina-woo-quotes/emails/iwq-reminder.php`.
 *
 * @package ImaginaWooQuotes
 *
 * @var WC_Order  $order              Pedido.
 * @var IWQ_Quote $quote              Presupuesto.
 * @var string    $additional_content Contenido adicional configurado.
 * @var bool      $sent_to_admin      Si el email va al administrador.
 * @var WC_Email  $email              Email en curso.
 */

defined( 'ABSPATH' ) || exit;

$iwq_part = static function ( $part, $args = array() ) use ( $order, $quote, $sent_to_admin ) {
	iwq_get_template( 'emails/parts/' . $part . '.php', array_merge( compact( 'order', 'quote', 'sent_to_admin' ), $args ) );
};
$iwq_days = $quote->get_days_to_expiry();
?>
<p>
	<?php
	printf(
		/* translators: %s: nombre del cliente. */
		esc_html__( 'Hola %s:', 'imagina-woo-quotes' ),
		esc_html( $order->get_billing_first_name() )
	);
	?>
</p>
<p>
	<?php
	if ( null !== $iwq_days && $iwq_days > 0 ) {
		printf(
			esc_html(
				/* translators: %s: número de días. */
				_n( 'Tu presupuesto vence en %s día. Si sigue interesándote, puedes aceptarlo con un clic.', 'Tu presupuesto vence en %s días. Si sigue interesándote, puedes aceptarlo con un clic.', $iwq_days, 'imagina-woo-quotes' )
			),
			esc_html( number_format_i18n( $iwq_days ) )
		);
	} else {
		esc_html_e( 'Tu presupuesto está a punto de vencer. Si sigue interesándote, puedes aceptarlo con un clic.', 'imagina-woo-quotes' );
	}
	?>
</p>

<?php
$iwq_part( 'summary' );
$iwq_part( 'items' );

if ( $quote->is_actionable() ) {
	$iwq_part(
		'cta',
		array(
			'buttons' => array(
				array( 'url' => $quote->get_accept_url(), 'label' => __( 'Aceptar el presupuesto', 'imagina-woo-quotes' ), 'primary' => true ),
				array( 'url' => $quote->get_reject_url(), 'label' => __( 'Rechazarlo', 'imagina-woo-quotes' ), 'primary' => false ),
			),
		)
	);
}

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
