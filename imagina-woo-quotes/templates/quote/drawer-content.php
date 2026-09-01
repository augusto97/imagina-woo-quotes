<?php
/**
 * Contenido del panel lateral.
 *
 * @package ImaginaWooQuotes
 *
 * @var array $items Líneas de la lista de presupuesto.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $items ) ) :
	?>
	<p class="iwq-empty"><?php esc_html_e( 'Tu lista de presupuesto está vacía.', 'imagina-woo-quotes' ); ?></p>
	<?php
	return;
endif;
?>
<ul class="iwq-list">
	<?php
	foreach ( $items as $iwq_key => $iwq_item ) :
		$iwq_product = wc_get_product( $iwq_item['variation_id'] ? $iwq_item['variation_id'] : $iwq_item['product_id'] );

		if ( ! $iwq_product ) {
			continue;
		}
		?>
		<li class="iwq-list__row" data-item-key="<?php echo esc_attr( $iwq_key ); ?>">
			<a class="iwq-list__thumb" href="<?php echo esc_url( $iwq_product->get_permalink() ); ?>">
				<?php echo wp_kses_post( $iwq_product->get_image( 'woocommerce_thumbnail' ) ); ?>
			</a>

			<div class="iwq-list__details">
				<a class="iwq-list__name" href="<?php echo esc_url( $iwq_product->get_permalink() ); ?>">
					<?php echo esc_html( $iwq_product->get_name() ); ?>
				</a>

				<?php if ( ! empty( $iwq_item['variation'] ) ) : ?>
					<div class="iwq-list__meta">
						<?php echo wp_kses_post( wc_get_formatted_variation( $iwq_item['variation'], true ) ); ?>
					</div>
				<?php endif; ?>

				<label class="iwq-sr-only" for="iwq-qty-<?php echo esc_attr( $iwq_key ); ?>">
					<?php esc_html_e( 'Cantidad', 'imagina-woo-quotes' ); ?>
				</label>

				<input
					type="number"
					class="iwq-quantity"
					id="iwq-qty-<?php echo esc_attr( $iwq_key ); ?>"
					value="<?php echo esc_attr( $iwq_item['quantity'] ); ?>"
					min="1"
					step="1"
					inputmode="numeric"
				>
			</div>

			<button type="button" class="iwq-remove-item" aria-label="
				<?php
				echo esc_attr(
					sprintf(
						/* translators: %s: nombre del producto. */
						__( 'Quitar %s de la lista', 'imagina-woo-quotes' ),
						$iwq_product->get_name()
					)
				);
				?>
			">
				<svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
					<path d="M6 6l8 8M14 6l-8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</button>
		</li>
	<?php endforeach; ?>
</ul>
