<?php
/**
 * Email: nueva solicitud para el administrador.
 *
 * Devuelve solo el cuerpo; el envoltorio lo pone emails/layout.php según el
 * diseño elegido. Se puede sobreescribir copiándolo a
 * `tu-tema/imagina-woo-quotes/emails/iwq-new-request.php`.
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
		esc_html__( '%s acaba de solicitar un presupuesto. Estos son los datos para prepararlo:', 'imagina-woo-quotes' ),
		'<strong>' . esc_html( $order->get_formatted_billing_full_name() ) . '</strong>'
	);
	?>
</p>

<?php
$iwq_part( 'summary' );
$iwq_part( 'customer' );
$iwq_part( 'items' );
$iwq_part( 'form-data' );
$iwq_part( 'cta', array( 'buttons' => array( array( 'url' => $order->get_edit_order_url(), 'label' => __( 'Preparar el presupuesto', 'imagina-woo-quotes' ), 'primary' => true ) ) ) );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
