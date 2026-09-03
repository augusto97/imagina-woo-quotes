<?php
/**
 * Lista de productos con la misma tabla que el carrito de WooCommerce.
 *
 * Reproduce la estructura y las clases de `cart/cart.php` para que el tema
 * la pinte exactamente igual que el carrito: mismas columnas, misma caja
 * de cantidad, mismo aspa de quitar y mismos botones.
 *
 * @package ImaginaWooQuotes
 *
 * @var array $items Líneas de la lista de presupuesto.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $items ) ) {
	return;
}

$iwq_rows = array();

foreach ( $items as $iwq_key => $iwq_item ) {
	$iwq_product = wc_get_product( $iwq_item['variation_id'] ? $iwq_item['variation_id'] : $iwq_item['product_id'] );

	if ( $iwq_product ) {
		$iwq_rows[ $iwq_key ] = array(
			'item'       => $iwq_item,
			'product'    => $iwq_product,
			'show_price' => ! IWQ_Exclusions::should_hide_price( $iwq_product ) && '' !== $iwq_product->get_price(),
		);
	}
}

$iwq_any_price = (bool) array_filter( array_column( $iwq_rows, 'show_price' ) );
$iwq_columns   = $iwq_any_price ? 6 : 4;
$iwq_button    = IWQ_Design::get_theme_button_class();
?>
<div class="iwq-cart">
	<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents iwq-cart-table" cellspacing="0">
		<thead>
			<tr>
				<th class="product-remove"><span class="screen-reader-text"><?php esc_html_e( 'Quitar', 'imagina-woo-quotes' ); ?></span></th>
				<th class="product-thumbnail"><span class="screen-reader-text"><?php esc_html_e( 'Imagen', 'imagina-woo-quotes' ); ?></span></th>
				<th scope="col" class="product-name"><?php esc_html_e( 'Producto', 'woocommerce' ); ?></th>
				<?php if ( $iwq_any_price ) : ?>
					<th scope="col" class="product-price"><?php esc_html_e( 'Precio', 'woocommerce' ); ?></th>
				<?php endif; ?>
				<th scope="col" class="product-quantity"><?php esc_html_e( 'Cantidad', 'woocommerce' ); ?></th>
				<?php if ( $iwq_any_price ) : ?>
					<th scope="col" class="product-subtotal"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
				<?php endif; ?>
			</tr>
		</thead>

		<tbody>
			<?php foreach ( $iwq_rows as $iwq_key => $iwq_row ) : ?>
				<?php
				$iwq_product = $iwq_row['product'];
				$iwq_item    = $iwq_row['item'];
				$iwq_qty     = max( 1, (int) $iwq_item['quantity'] );
				$iwq_max     = $iwq_product->managing_stock() && ! $iwq_product->backorders_allowed() ? (int) $iwq_product->get_stock_quantity() : '';
				?>
				<tr class="woocommerce-cart-form__cart-item cart_item iwq-cart-row" data-item-key="<?php echo esc_attr( $iwq_key ); ?>">
					<td class="product-remove">
						<a
							role="button"
							href="#"
							class="remove iwq-remove-item"
							aria-label="<?php echo esc_attr( sprintf( /* translators: %s: nombre del producto. */ __( 'Quitar %s de la lista', 'imagina-woo-quotes' ), $iwq_product->get_name() ) ); ?>"
							data-product_id="<?php echo esc_attr( $iwq_product->get_id() ); ?>"
						>&times;</a>
					</td>

					<td class="product-thumbnail">
						<a href="<?php echo esc_url( $iwq_product->get_permalink() ); ?>"><?php echo wp_kses_post( $iwq_product->get_image() ); ?></a>
					</td>

					<td scope="row" role="rowheader" class="product-name" data-title="<?php esc_attr_e( 'Producto', 'woocommerce' ); ?>">
						<a href="<?php echo esc_url( $iwq_product->get_permalink() ); ?>"><?php echo esc_html( $iwq_product->get_name() ); ?></a>
						<?php if ( ! empty( $iwq_item['variation'] ) ) : ?>
							<?php echo wp_kses_post( wc_get_formatted_variation( $iwq_item['variation'] ) ); ?>
						<?php endif; ?>
					</td>

					<?php if ( $iwq_any_price ) : ?>
						<td class="product-price" data-title="<?php esc_attr_e( 'Precio', 'woocommerce' ); ?>">
							<?php echo $iwq_row['show_price'] ? wp_kses_post( wc_price( wc_get_price_to_display( $iwq_product ) ) ) : '&mdash;'; ?>
						</td>
					<?php endif; ?>

					<td class="product-quantity" data-title="<?php esc_attr_e( 'Cantidad', 'woocommerce' ); ?>">
						<?php
						woocommerce_quantity_input(
							array(
								'input_id'     => 'iwq-qty-' . $iwq_key,
								'input_name'   => 'iwq_qty[' . $iwq_key . ']',
								'input_value'  => $iwq_qty,
								'classes'      => array( 'input-text', 'qty', 'text', 'iwq-quantity' ),
								'min_value'    => 1,
								'max_value'    => $iwq_max ? $iwq_max : '',
								'product_name' => $iwq_product->get_name(),
							),
							$iwq_product
						);
						?>
					</td>

					<?php if ( $iwq_any_price ) : ?>
						<td class="product-subtotal iwq-line-subtotal" data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>">
							<?php echo $iwq_row['show_price'] ? wp_kses_post( wc_price( wc_get_price_to_display( $iwq_product, array( 'qty' => $iwq_qty ) ) ) ) : '&mdash;'; ?>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>

			<tr>
				<td colspan="<?php echo esc_attr( $iwq_columns ); ?>" class="actions">
					<button type="button" class="<?php echo esc_attr( $iwq_button ); ?> iwq-clear-list"><?php esc_html_e( 'Vaciar la lista', 'imagina-woo-quotes' ); ?></button>
					<a class="<?php echo esc_attr( $iwq_button ); ?> wc-backward iwq-continue" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Seguir viendo productos', 'imagina-woo-quotes' ); ?></a>
				</td>
			</tr>
		</tbody>
	</table>
</div>
