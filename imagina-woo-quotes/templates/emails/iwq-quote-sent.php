<?php
/**
 * Email: presupuesto valorado para el cliente.
 *
 * Devuelve solo el cuerpo; el envoltorio lo pone emails/layout.php según el
 * diseño elegido. Se puede sobreescribir copiándolo a
 * `tu-tema/imagina-woo-quotes/emails/iwq-quote-sent.php`.
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
<p><?php esc_html_e( 'Ya tenemos listo tu presupuesto. Aquí tienes el detalle; también va adjunto en PDF.', 'imagina-woo-quotes' ); ?></p>

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

	if ( iwq_option_enabled( 'allow_counter_offers', true ) && $order->get_customer_id() ) {
		echo '<p class="iwq-muted">';
		printf(
			/* translators: %s: enlace a Mi Cuenta. */
			esc_html__( '¿Quieres proponer otro precio? Puedes hacerlo desde %s.', 'imagina-woo-quotes' ),
			'<a href="' . esc_url( $order->get_view_order_url() ) . '">' . esc_html__( 'tu cuenta', 'imagina-woo-quotes' ) . '</a>'
		);
		echo '</p>';
	}
}

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
