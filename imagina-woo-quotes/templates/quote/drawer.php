<?php
/**
 * Panel lateral con la lista de presupuesto.
 *
 * Misma estructura que el mini carrito de WooCommerce: título con
 * contador, filas con imagen, nombre, precio, control de cantidad y quitar,
 * y un pie con subtotal y dos botones del tema. Se imprime vacío y el
 * contenido llega por AJAX, así que el HTML de la página sigue siendo el
 * mismo para todos los visitantes y la caché de página completa funciona.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

$iwq_drawer_classes = array_merge( array( 'iwq', 'iwq-drawer', 'woocommerce' ), IWQ_Design::get_drawer_classes() );
$iwq_drawer_title   = IWQ_Design::get( 'drawer_title' );
$iwq_footer_label   = IWQ_Design::get( 'drawer_footer_label' );
$iwq_continue_label = IWQ_Design::get( 'drawer_continue_label' );
$iwq_subtotal_desc  = IWQ_Design::get( 'drawer_subtotal_desc' );
$iwq_button         = IWQ_Design::get_theme_button_class();
$iwq_quote_url      = get_permalink( (int) iwq_get_option( 'quote_page_id' ) );
$iwq_actions_class  = 'row' === IWQ_Design::get( 'drawer_buttons_layout' ) ? 'iwq-drawer__actions--row' : 'iwq-drawer__actions--stacked';
?>
<div id="iwq-drawer" class="<?php echo esc_attr( implode( ' ', $iwq_drawer_classes ) ); ?>" role="dialog" aria-modal="true" aria-labelledby="iwq-drawer-title" hidden>
	<div class="iwq-drawer__overlay"></div>

	<div class="iwq-drawer__panel">
		<div class="iwq-drawer__header">
			<h2 id="iwq-drawer-title" class="iwq-drawer__title">
				<?php echo esc_html( $iwq_drawer_title ? $iwq_drawer_title : __( 'Tu presupuesto', 'imagina-woo-quotes' ) ); ?>
				<?php if ( 'no' !== IWQ_Design::get( 'drawer_show_count' ) ) : ?>
					<span class="iwq-drawer__count" hidden></span>
				<?php endif; ?>
			</h2>

			<button type="button" class="iwq-drawer__close" aria-label="<?php esc_attr_e( 'Cerrar el panel', 'imagina-woo-quotes' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
					<path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
				</svg>
			</button>
		</div>

		<div class="iwq-drawer__body" aria-live="polite"></div>

		<div class="iwq-drawer__empty iwq-empty" hidden>
			<p><strong><?php esc_html_e( 'Tu lista de presupuesto está vacía.', 'imagina-woo-quotes' ); ?></strong></p>
			<a class="<?php echo esc_attr( $iwq_button ); ?> iwq-drawer__shop" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<?php esc_html_e( 'Ver el catálogo', 'imagina-woo-quotes' ); ?>
			</a>
		</div>

		<div class="iwq-drawer__footer iwq-has-items" hidden>
			<?php if ( 'no' !== IWQ_Design::get( 'drawer_show_subtotal' ) ) : ?>
				<div class="iwq-drawer__subtotal" hidden>
					<span class="iwq-drawer__subtotal-label"><?php esc_html_e( 'Subtotal', 'imagina-woo-quotes' ); ?></span>
					<span class="iwq-drawer__subtotal-value"></span>
					<?php if ( '-' !== trim( $iwq_subtotal_desc ) ) : ?>
						<div class="iwq-drawer__subtotal-desc"><?php echo esc_html( $iwq_subtotal_desc ? $iwq_subtotal_desc : __( 'Precios de catálogo, orientativos. Te confirmaremos el presupuesto.', 'imagina-woo-quotes' ) ); ?></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="iwq-drawer__actions <?php echo esc_attr( $iwq_actions_class ); ?>">
				<?php if ( 'no' !== IWQ_Design::get( 'drawer_show_continue' ) ) : ?>
					<button type="button" class="<?php echo esc_attr( $iwq_button ); ?> iwq-drawer__continue">
						<?php echo esc_html( $iwq_continue_label ? $iwq_continue_label : __( 'Seguir viendo productos', 'imagina-woo-quotes' ) ); ?>
					</button>
				<?php endif; ?>
				<a class="<?php echo esc_attr( IWQ_Design::get_theme_button_class( true ) ); ?> iwq-drawer__submit" href="<?php echo esc_url( $iwq_quote_url ); ?>">
					<?php echo esc_html( $iwq_footer_label ? $iwq_footer_label : __( 'Ver y enviar la solicitud', 'imagina-woo-quotes' ) ); ?>
				</a>
			</div>
		</div>
	</div>
</div>
