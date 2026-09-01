<?php
/**
 * Diseño de reserva cuando no hay ninguna plantilla configurada.
 *
 * Reutiliza los mismos bloques que la plantilla del editor, así el resultado
 * es coherente aunque el comerciante nunca entre a diseñarla.
 *
 * @package ImaginaWooQuotes
 *
 * @var WC_Order $order Pedido.
 */

defined( 'ABSPATH' ) || exit;
?>
<?php echo IWQ_PDF_Blocks::render_store_logo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- el bloque escapa su salida. ?>

<h1><?php echo esc_html( sprintf( /* translators: %s: número de presupuesto. */ __( 'Presupuesto %s', 'imagina-woo-quotes' ), $order->get_order_number() ) ); ?></h1>

<?php
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- cada bloque escapa su salida.
echo IWQ_PDF_Blocks::render_quote_meta();
echo IWQ_PDF_Blocks::render_customer_info();
echo IWQ_PDF_Blocks::render_quote_table( array( 'showImages' => true ) );
echo IWQ_PDF_Blocks::render_quote_totals();
echo IWQ_PDF_Blocks::render_form_data();
echo IWQ_PDF_Blocks::render_quote_actions();
// phpcs:enable
?>
