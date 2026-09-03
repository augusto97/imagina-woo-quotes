<?php
/**
 * Botón «Solicitar presupuesto».
 *
 * Se puede sobreescribir copiándolo a
 * `tu-tema/imagina-woo-quotes/quote/add-to-quote-button.php`.
 *
 * @package ImaginaWooQuotes
 *
 * @var WC_Product $product     Producto.
 * @var string     $context     `single` o `loop`.
 * @var bool       $in_list     Si el producto ya está en la lista.
 * @var bool       $is_variable Si es un producto variable.
 */

defined( 'ABSPATH' ) || exit;

$iwq_label = $in_list
	? iwq_get_option( 'button_label_added', __( 'Ya está en tu presupuesto', 'imagina-woo-quotes' ) )
	: iwq_get_option( 'button_label', __( 'Solicitar presupuesto', 'imagina-woo-quotes' ) );

$iwq_classes = array( 'iwq', 'iwq-add-button', 'iwq-add-button--' . $context );

if ( $in_list ) {
	$iwq_classes[] = 'is-added';
}

$iwq_classes = array_merge( $iwq_classes, IWQ_Design::get_button_classes() );
?>
<button
	type="button"
	class="<?php echo esc_attr( implode( ' ', $iwq_classes ) ); ?>"
	data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
	data-label="<?php echo esc_attr( iwq_get_option( 'button_label', __( 'Solicitar presupuesto', 'imagina-woo-quotes' ) ) ); ?>"
>
	<span class="iwq-add-button__spinner" aria-hidden="true"></span>
	<span class="iwq-add-button__label"><?php echo esc_html( $iwq_label ); ?></span>
</button>
