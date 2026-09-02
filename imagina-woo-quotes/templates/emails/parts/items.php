<?php
/**
 * Tabla de productos con miniaturas, y totales.
 *
 * @package ImaginaWooQuotes
 *
 * @var WC_Order  $order Pedido.
 * @var IWQ_Quote $quote Presupuesto.
 */

defined( 'ABSPATH' ) || exit;

$iwq_priced = $quote->prices_visible();
$iwq_args   = array( 'currency' => $order->get_currency() );
?>
<h2 class="iwq-h2"><?php esc_html_e( 'Productos', 'imagina-woo-quotes' ); ?></h2>

<table class="iwq-items" cellpadding="0" cellspacing="0" role="presentation">
	<thead>
		<tr>
			<th colspan="2"><?php esc_html_e( 'Producto', 'imagina-woo-quotes' ); ?></th>
			<th class="iwq-num"><?php esc_html_e( 'Cant.', 'imagina-woo-quotes' ); ?></th>
			<?php if ( $iwq_priced ) : ?>
				<th class="iwq-num"><?php esc_html_e( 'Precio', 'imagina-woo-quotes' ); ?></th>
				<th class="iwq-num"><?php esc_html_e( 'Total', 'imagina-woo-quotes' ); ?></th>
			<?php endif; ?>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ( $order->get_items() as $iwq_item_id => $iwq_item ) :
			$iwq_product = $iwq_item->get_product();
			$iwq_qty     = $iwq_item->get_quantity();
			$iwq_unit    = $iwq_qty ? (float) $iwq_item->get_total() / $iwq_qty : 0;
			$iwq_list    = $quote->get_list_price( $iwq_item_id );
			$iwq_thumb   = $iwq_product ? wp_get_attachment_image_url( $iwq_product->get_image_id(), 'thumbnail' ) : '';
			?>
			<tr>
				<td style="width:56px;">
					<?php if ( $iwq_thumb ) : ?>
						<img src="<?php echo esc_url( $iwq_thumb ); ?>" alt="">
					<?php endif; ?>
				</td>
				<td>
					<span class="iwq-name"><?php echo esc_html( $iwq_item->get_name() ); ?></span>
					<?php if ( $iwq_product && $iwq_product->get_sku() ) : ?>
						<br><span class="iwq-meta"><?php echo esc_html( $iwq_product->get_sku() ); ?></span>
					<?php endif; ?>
					<?php echo wp_kses_post( wc_display_item_meta( $iwq_item, array( 'echo' => false, 'before' => '<br><span class="iwq-meta">', 'after' => '</span>', 'separator' => ', ' ) ) ); ?>
				</td>
				<td class="iwq-num"><?php echo esc_html( $iwq_qty ); ?></td>
				<?php if ( $iwq_priced ) : ?>
					<td class="iwq-num">
						<?php if ( $iwq_list && $iwq_list > $iwq_unit ) : ?>
							<del><?php echo wp_kses_post( wc_price( $iwq_list, $iwq_args ) ); ?></del><br>
						<?php endif; ?>
						<?php echo wp_kses_post( wc_price( $iwq_unit, $iwq_args ) ); ?>
					</td>
					<td class="iwq-num"><?php echo wp_kses_post( wc_price( $iwq_item->get_total(), $iwq_args ) ); ?></td>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<?php if ( $iwq_priced ) : ?>
	<table class="iwq-totals" cellpadding="0" cellspacing="0" role="presentation">
		<?php
		$iwq_totals = $order->get_order_item_totals();
		$iwq_last   = array_key_last( $iwq_totals );

		foreach ( $iwq_totals as $iwq_key => $iwq_total ) :
			?>
			<tr class="<?php echo $iwq_key === $iwq_last ? 'iwq-grand' : ''; ?>">
				<td class="iwq-label"><?php echo esc_html( rtrim( $iwq_total['label'], ':' ) ); ?></td>
				<td class="iwq-value"><?php echo wp_kses_post( $iwq_total['value'] ); ?></td>
			</tr>
		<?php endforeach; ?>
	</table>

	<?php if ( $quote->is_estimate() ) : ?>
		<div class="iwq-note"><?php esc_html_e( 'Precios de catálogo, orientativos. Te confirmaremos el presupuesto definitivo en breve.', 'imagina-woo-quotes' ); ?></div>
	<?php endif; ?>
<?php else : ?>
	<div class="iwq-note"><?php esc_html_e( 'Precios pendientes de valoración. Te enviaremos el presupuesto en breve.', 'imagina-woo-quotes' ); ?></div>
<?php endif; ?>
