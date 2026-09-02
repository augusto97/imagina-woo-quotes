<?php
/**
 * Datos del cliente, para los emails del administrador.
 *
 * @package ImaginaWooQuotes
 *
 * @var WC_Order $order Pedido.
 */

defined( 'ABSPATH' ) || exit;

$iwq_rows = array_filter(
	array(
		__( 'Nombre', 'imagina-woo-quotes' )   => $order->get_formatted_billing_full_name(),
		__( 'Empresa', 'imagina-woo-quotes' )  => $order->get_billing_company(),
		__( 'Email', 'imagina-woo-quotes' )    => $order->get_billing_email(),
		__( 'Teléfono', 'imagina-woo-quotes' ) => $order->get_billing_phone(),
	)
);
?>
<h2 class="iwq-h2"><?php esc_html_e( 'Cliente', 'imagina-woo-quotes' ); ?></h2>
<table class="iwq-form" cellpadding="0" cellspacing="0" role="presentation">
	<?php foreach ( $iwq_rows as $iwq_label => $iwq_value ) : ?>
		<tr>
			<td class="iwq-label"><?php echo esc_html( $iwq_label ); ?></td>
			<td>
				<?php if ( __( 'Email', 'imagina-woo-quotes' ) === $iwq_label ) : ?>
					<a href="mailto:<?php echo esc_attr( $iwq_value ); ?>"><?php echo esc_html( $iwq_value ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $iwq_value ); ?>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
</table>
