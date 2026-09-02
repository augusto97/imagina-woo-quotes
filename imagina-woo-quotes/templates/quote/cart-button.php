<?php
/**
 * Botón que convierte el carrito completo en una solicitud de presupuesto.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;
?>
<a class="iwq iwq-add-button iwq-add-button--outline iwq-cart-to-quote" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'iwq_cart_to_quote', '1', wc_get_cart_url() ), 'iwq_cart_to_quote' ) ); ?>">
	<?php esc_html_e( 'Pedir presupuesto de estos productos', 'imagina-woo-quotes' ); ?>
</a>
