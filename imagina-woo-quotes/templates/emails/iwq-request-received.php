<?php
/**
 * Email: confirmación de solicitud para el cliente.
 *
 * Devuelve solo el cuerpo; el envoltorio lo pone emails/layout.php según el
 * diseño elegido. Se puede sobreescribir copiándolo a
 * `tu-tema/imagina-woo-quotes/emails/iwq-request-received.php`.
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
<p><?php esc_html_e( 'Hemos recibido tu solicitud y ya la estamos revisando. En cuanto tengamos el presupuesto preparado te lo enviaremos a este mismo correo.', 'imagina-woo-quotes' ); ?></p>

<?php
$iwq_part( 'summary' );
$iwq_part( 'items' );
$iwq_part( 'form-data' );

if ( is_user_logged_in() || $order->get_customer_id() ) {
	$iwq_part( 'cta', array( 'buttons' => array( array( 'url' => $order->get_view_order_url(), 'label' => __( 'Ver mi solicitud', 'imagina-woo-quotes' ), 'primary' => false ) ) ) );
}

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
