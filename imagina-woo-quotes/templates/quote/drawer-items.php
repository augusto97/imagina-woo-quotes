<?php
/**
 * Filas del panel lateral, con la disposición del mini carrito de
 * WooCommerce: imagen, detalles con control de cantidad y quitar, y total
 * de línea.
 *
 * @package ImaginaWooQuotes
 *
 * @var array $items Líneas de la lista de presupuesto.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $items ) ) {
	return;
}
?>
<table class="iwq-mini-items">
	<tbody>
		<?php
		foreach ( $items as $iwq_key => $iwq_item ) :
			$iwq_product = wc_get_product( $iwq_item['variation_id'] ? $iwq_item['variation_id'] : $iwq_item['product_id'] );

			if ( ! $iwq_product ) {
				continue;
			}

			$iwq_qty        = max( 1, (int) $iwq_item['quantity'] );
			$iwq_show_price = ! IWQ_Exclusions::should_hide_price( $iwq_product ) && '' !== $iwq_product->get_price();
			$iwq_max        = $iwq_product->managing_stock() && ! $iwq_product->backorders_allowed() ? (int) $iwq_product->get_stock_quantity() : 0;
			$iwq_name       = $iwq_product->get_name();
			?>
			<tr class="iwq-mini-item" data-item-key="<?php echo esc_attr( $iwq_key ); ?>">
				<td class="iwq-mini-item__image">
					<a href="<?php echo esc_url( $iwq_product->get_permalink() ); ?>" tabindex="-1" aria-hidden="true">
						<?php echo wp_kses_post( $iwq_product->get_image( 'woocommerce_thumbnail' ) ); ?>
					</a>
				</td>

				<td class="iwq-mini-item__product">
					<div class="iwq-mini-item__wrap">
					<a class="iwq-mini-item__name" href="<?php echo esc_url( $iwq_product->get_permalink() ); ?>"><?php echo esc_html( $iwq_name ); ?></a>

					<?php if ( $iwq_show_price ) : ?>
						<div class="iwq-mini-item__price"><?php echo wp_kses_post( wc_price( wc_get_price_to_display( $iwq_product ) ) ); ?></div>
					<?php endif; ?>

					<?php if ( ! empty( $iwq_item['variation'] ) ) : ?>
						<div class="iwq-mini-item__meta"><?php echo wp_kses_post( wc_get_formatted_variation( $iwq_item['variation'], true ) ); ?></div>
					<?php endif; ?>

					<div class="iwq-mini-item__controls">
						<div class="iwq-qty">
							<label class="iwq-sr-only" for="iwq-qty-<?php echo esc_attr( $iwq_key ); ?>">
								<?php echo esc_html( sprintf( /* translators: %s: nombre del producto. */ __( 'Cantidad de %s', 'imagina-woo-quotes' ), $iwq_name ) ); ?>
							</label>
							<input
								type="number"
								class="iwq-qty__input iwq-quantity"
								id="iwq-qty-<?php echo esc_attr( $iwq_key ); ?>"
								value="<?php echo esc_attr( $iwq_qty ); ?>"
								min="1"
								<?php echo $iwq_max ? 'max="' . esc_attr( $iwq_max ) . '"' : ''; ?>
								step="1"
								inputmode="numeric"
							>
							<button type="button" class="iwq-qty__button iwq-qty__button--minus" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: nombre del producto. */ __( 'Reducir la cantidad de %s', 'imagina-woo-quotes' ), $iwq_name ) ); ?>">&minus;</button>
							<button type="button" class="iwq-qty__button iwq-qty__button--plus" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: nombre del producto. */ __( 'Aumentar la cantidad de %s', 'imagina-woo-quotes' ), $iwq_name ) ); ?>">&#xFF0B;</button>
						</div>

						<button type="button" class="iwq-mini-item__remove iwq-remove-item" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: nombre del producto. */ __( 'Quitar %s de la lista', 'imagina-woo-quotes' ), $iwq_name ) ); ?>">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
								<path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M6 7l1 13h10l1-13"/><path d="M9 7V4h6v3"/>
							</svg>
						</button>
					</div>
					</div>
				</td>

				<td class="iwq-mini-item__total iwq-line-subtotal">
					<?php echo $iwq_show_price ? wp_kses_post( wc_price( wc_get_price_to_display( $iwq_product, array( 'qty' => $iwq_qty ) ) ) ) : ''; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
