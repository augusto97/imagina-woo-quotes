<?php
/**
 * Envoltorio HTML del PDF.
 *
 * @package ImaginaWooQuotes
 *
 * @var WC_Order  $order   Pedido.
 * @var IWQ_Quote $quote   Presupuesto.
 * @var string    $content Cuerpo ya renderizado.
 * @var string    $styles  CSS del documento.
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<title><?php echo esc_html( sprintf( /* translators: %s: número de presupuesto. */ __( 'Presupuesto %s', 'imagina-woo-quotes' ), $order->get_order_number() ) ); ?></title>
	<style><?php echo $styles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS propio. ?></style>
</head>
<body>
	<?php if ( iwq_option_enabled( 'pdf_show_footer', true ) ) : ?>
		<div class="iwq-pdf-footer">
			<?php echo wp_kses_post( iwq_get_option( 'pdf_footer_text', get_bloginfo( 'name' ) ) ); ?>
		</div>
	<?php endif; ?>

	<main class="iwq-pdf-body">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- los bloques escapan su salida. ?>
	</main>
</body>
</html>
