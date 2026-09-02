<?php
/**
 * Email: cambio de estado para el administrador.
 *
 * Devuelve solo el cuerpo; el envoltorio lo pone emails/layout.php según el
 * diseño elegido. Se puede sobreescribir copiándolo a
 * `tu-tema/imagina-woo-quotes/emails/iwq-quote-status.php`.
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
$iwq_accepted = 'iwq-accepted' === $order->get_status();
?>
<p>
	<?php
	printf(
		/* translators: 1: nombre del cliente, 2: número de presupuesto, 3: estado. */
		esc_html__( '%1$s ha respondido al presupuesto #%2$s: %3$s.', 'imagina-woo-quotes' ),
		'<strong>' . esc_html( $order->get_formatted_billing_full_name() ) . '</strong>',
		esc_html( $order->get_order_number() ),
		'<strong>' . esc_html( $iwq_accepted ? __( 'aceptado', 'imagina-woo-quotes' ) : __( 'rechazado', 'imagina-woo-quotes' ) ) . '</strong>'
	);
	?>
</p>

<?php
if ( $iwq_accepted ) {
	echo '<div class="iwq-note">' . esc_html__( 'El cliente ya puede pagar desde el enlace de su cuenta. Cuando complete el pago, el pedido seguirá el flujo normal de WooCommerce.', 'imagina-woo-quotes' ) . '</div>';
}

$iwq_part( 'summary' );
$iwq_part( 'customer' );
$iwq_part( 'items' );
$iwq_part( 'cta', array( 'buttons' => array( array( 'url' => $order->get_edit_order_url(), 'label' => __( 'Ver el pedido', 'imagina-woo-quotes' ), 'primary' => true ) ) ) );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
