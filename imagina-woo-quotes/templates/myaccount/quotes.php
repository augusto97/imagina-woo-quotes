<?php
/**
 * Listado de presupuestos en Mi Cuenta.
 *
 * @package ImaginaWooQuotes
 *
 * @var WC_Order[] $orders       Presupuestos del cliente.
 * @var int        $total_pages  Número total de páginas.
 * @var int        $current_page Página actual.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $orders ) ) :
	?>
	<div class="iwq iwq-empty">
		<p><?php esc_html_e( 'Todavía no has solicitado ningún presupuesto.', 'imagina-woo-quotes' ); ?></p>

		<a class="iwq-add-button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
			<?php esc_html_e( 'Ver el catálogo', 'imagina-woo-quotes' ); ?>
		</a>
	</div>
	<?php
	return;
endif;
?>
<table class="iwq woocommerce-orders-table iwq-quotes-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Presupuesto', 'imagina-woo-quotes' ); ?></th>
			<th><?php esc_html_e( 'Fecha', 'imagina-woo-quotes' ); ?></th>
			<th><?php esc_html_e( 'Estado', 'imagina-woo-quotes' ); ?></th>
			<th><?php esc_html_e( 'Total', 'imagina-woo-quotes' ); ?></th>
			<th><span class="iwq-sr-only"><?php esc_html_e( 'Acciones', 'imagina-woo-quotes' ); ?></span></th>
		</tr>
	</thead>

	<tbody>
		<?php
		foreach ( $orders as $iwq_order ) :
			$iwq_quote = iwq_get_quote( $iwq_order );
			?>
			<tr>
				<td data-title="<?php esc_attr_e( 'Presupuesto', 'imagina-woo-quotes' ); ?>">
					<a href="<?php echo esc_url( $iwq_order->get_view_order_url() ); ?>">
						#<?php echo esc_html( $iwq_order->get_order_number() ); ?>
					</a>
				</td>

				<td data-title="<?php esc_attr_e( 'Fecha', 'imagina-woo-quotes' ); ?>">
					<time datetime="<?php echo esc_attr( $iwq_order->get_date_created()->date( 'c' ) ); ?>">
						<?php echo esc_html( wc_format_datetime( $iwq_order->get_date_created() ) ); ?>
					</time>
				</td>

				<td data-title="<?php esc_attr_e( 'Estado', 'imagina-woo-quotes' ); ?>">
					<span class="iwq-status" style="color:<?php echo esc_attr( iwq_get_status_color( $iwq_order->get_status() ) ); ?>">
						<?php echo esc_html( iwq_get_status_label( $iwq_order->get_status() ) ); ?>
					</span>
				</td>

				<td data-title="<?php esc_attr_e( 'Total', 'imagina-woo-quotes' ); ?>">
					<?php
					// Sin precios visibles no hay importe que mostrar.
					echo $iwq_quote && $iwq_quote->prices_visible()
						? wp_kses_post( $iwq_order->get_formatted_order_total() )
						: '—';
					?>
				</td>

				<td class="iwq-quotes-table__actions">
					<a class="iwq-add-button iwq-add-button--outline" href="<?php echo esc_url( $iwq_order->get_view_order_url() ); ?>">
						<?php esc_html_e( 'Ver', 'imagina-woo-quotes' ); ?>
					</a>

					<?php if ( iwq_option_enabled( 'pdf_enabled', true ) && IWQ_PDF::is_available() ) : ?>
						<a class="iwq-add-button iwq-add-button--outline" href="<?php echo esc_url( IWQ_PDF::get_download_url( $iwq_order ) ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'PDF', 'imagina-woo-quotes' ); ?>
						</a>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<?php if ( $total_pages > 1 ) : ?>
	<nav class="woocommerce-pagination" aria-label="<?php esc_attr_e( 'Paginación de presupuestos', 'imagina-woo-quotes' ); ?>">
		<?php if ( $current_page > 1 ) : ?>
			<a class="button" href="<?php echo esc_url( wc_get_endpoint_url( 'presupuestos', $current_page - 1 ) ); ?>">
				<?php esc_html_e( 'Anterior', 'imagina-woo-quotes' ); ?>
			</a>
		<?php endif; ?>

		<?php if ( $current_page < $total_pages ) : ?>
			<a class="button" href="<?php echo esc_url( wc_get_endpoint_url( 'presupuestos', $current_page + 1 ) ); ?>">
				<?php esc_html_e( 'Siguiente', 'imagina-woo-quotes' ); ?>
			</a>
		<?php endif; ?>
	</nav>
<?php endif; ?>
