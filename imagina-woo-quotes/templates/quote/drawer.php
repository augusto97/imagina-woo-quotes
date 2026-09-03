<?php
/**
 * Panel lateral con la lista de presupuesto.
 *
 * Se imprime vacío y el contenido llega por AJAX, de modo que el HTML de la
 * página sigue siendo el mismo para todos los visitantes y la caché de
 * página completa sigue sirviendo.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;
?>
<?php
$iwq_drawer_classes = array_merge( array( 'iwq', 'iwq-drawer' ), IWQ_Design::get_drawer_classes() );
$iwq_drawer_title   = IWQ_Design::get( 'drawer_title' );
$iwq_footer_label   = IWQ_Design::get( 'drawer_footer_label' );
?>
<div id="iwq-drawer" class="<?php echo esc_attr( implode( ' ', $iwq_drawer_classes ) ); ?>" role="dialog" aria-modal="true" aria-labelledby="iwq-drawer-title" hidden>
	<div class="iwq-drawer__overlay"></div>

	<div class="iwq-drawer__panel">
		<div class="iwq-drawer__header">
			<h2 id="iwq-drawer-title" class="iwq-drawer__title">
				<?php echo esc_html( $iwq_drawer_title ? $iwq_drawer_title : __( 'Tu presupuesto', 'imagina-woo-quotes' ) ); ?>
			</h2>

			<button type="button" class="iwq-drawer__close" aria-label="<?php esc_attr_e( 'Cerrar el panel', 'imagina-woo-quotes' ); ?>">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
					<path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</button>
		</div>

		<div class="iwq-drawer__body" aria-live="polite"></div>

		<div class="iwq-drawer__footer">
			<a class="iwq-add-button" href="<?php echo esc_url( get_permalink( (int) iwq_get_option( 'quote_page_id' ) ) ); ?>">
				<?php echo esc_html( $iwq_footer_label ? $iwq_footer_label : __( 'Ver y enviar la solicitud', 'imagina-woo-quotes' ) ); ?>
			</a>
		</div>
	</div>
</div>
