<?php
/**
 * Pantalla del plugin: cabecera, navegación lateral y contenido de la pestaña.
 *
 * El contenedor conserva `.wrap` y un `h1` oculto para que los avisos de
 * WordPress (settings-updated, errores de saneado…) aterricen dentro del
 * panel, donde nuestro JavaScript los convierte en un aviso flotante.
 *
 * @package ImaginaWooQuotes
 *
 * @var array  $tabs     Pestañas con sus metadatos.
 * @var string $current  Pestaña activa.
 * @var array  $sections Secciones de la pestaña activa.
 */

defined( 'ABSPATH' ) || exit;

$iwq_base     = admin_url( 'admin.php?page=iwq-settings&tab=' );
$iwq_has_form = ! empty( $sections );
$iwq_groups   = array();

// WordPress borra settings-updated de la URL con un script en línea antes de
// que cargue el nuestro; la marca viaja en el DOM para anunciar el guardado.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo decide si mostrar un aviso.
$iwq_saved = isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'];

foreach ( $tabs as $iwq_slug => $iwq_tab ) {
	$iwq_groups[ $iwq_tab['group'] ][ $iwq_slug ] = $iwq_tab;
}
?>
<div class="wrap iwq-app" data-tab="<?php echo esc_attr( $current ); ?>"<?php echo $iwq_saved ? ' data-saved="1"' : ''; ?>>
	<h1 class="screen-reader-text"><?php esc_html_e( 'Presupuestos', 'imagina-woo-quotes' ); ?></h1>

	<header class="iwq-app__header">
		<div class="iwq-app__brand">
			<span class="iwq-app__mark" aria-hidden="true">IQ</span>
			<span><?php esc_html_e( 'Presupuestos', 'imagina-woo-quotes' ); ?></span>
			<span class="iwq-app__version">v<?php echo esc_html( IWQ_VERSION ); ?></span>
		</div>

		<div class="iwq-app__header-actions">
			<a class="iwq-btn iwq-btn--ghost iwq-btn--sm" href="<?php echo esc_url( IWQ_DOCS_URL ); ?>" target="_blank" rel="noopener" title="<?php esc_attr_e( 'Documentación', 'imagina-woo-quotes' ); ?>">
				<?php echo IWQ_Admin::icon( 'book' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG estático. ?>
				<span><?php esc_html_e( 'Documentación', 'imagina-woo-quotes' ); ?></span>
			</a>

			<a class="iwq-btn iwq-btn--ghost iwq-btn--sm" href="<?php echo esc_url( IWQ_Dashboard::get_orders_url() ); ?>" title="<?php esc_attr_e( 'Pedidos', 'imagina-woo-quotes' ); ?>">
				<?php echo IWQ_Admin::icon( 'orders' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG estático. ?>
				<span><?php esc_html_e( 'Pedidos', 'imagina-woo-quotes' ); ?></span>
			</a>

			<?php if ( $iwq_has_form ) : ?>
				<button type="submit" form="iwq-settings-form" class="iwq-btn iwq-btn--primary iwq-btn--sm">
					<?php echo IWQ_Admin::icon( 'save' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Guardar', 'imagina-woo-quotes' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</header>

	<div class="iwq-app__layout">

		<aside class="iwq-app__nav">
			<nav aria-label="<?php esc_attr_e( 'Secciones del plugin', 'imagina-woo-quotes' ); ?>">
				<?php foreach ( $iwq_groups as $iwq_group => $iwq_items ) : ?>
					<div class="iwq-nav__group"><?php echo esc_html( $iwq_group ); ?></div>

					<ul class="iwq-nav">
						<?php foreach ( $iwq_items as $iwq_slug => $iwq_tab ) : ?>
							<li class="iwq-nav__item<?php echo $iwq_slug === $current ? ' is-active' : ''; ?>">
								<a href="<?php echo esc_url( $iwq_base . $iwq_slug ); ?>"<?php echo $iwq_slug === $current ? ' aria-current="page"' : ''; ?>>
									<?php echo IWQ_Admin::icon( $iwq_tab['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php echo esc_html( $iwq_tab['label'] ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endforeach; ?>
			</nav>
		</aside>

		<main class="iwq-app__main">

			<div class="iwq-page-head">
				<h2><?php echo esc_html( $tabs[ $current ]['label'] ); ?></h2>
				<p><?php echo esc_html( $tabs[ $current ]['desc'] ); ?></p>
			</div>

			<?php if ( 'inicio' === $current ) : ?>

				<?php IWQ_Dashboard::render(); ?>

			<?php elseif ( 'stats' === $current ) : ?>

				<?php IWQ_Statistics::render(); ?>

			<?php elseif ( 'preview' === $current ) : ?>

				<?php IWQ_Preview::render(); ?>

			<?php else : ?>

				<?php if ( 'pdf' === $current && ! IWQ_PDF::is_available() ) : ?>
					<div class="iwq-alert iwq-alert--warning">
						<?php esc_html_e( 'La librería de PDF no está instalada. Instala el zip de la versión publicada o ejecuta «composer install --no-dev» en el directorio del plugin para activar la generación de documentos.', 'imagina-woo-quotes' ); ?>
					</div>
				<?php endif; ?>

				<form method="post" action="options.php" class="iwq-settings-form" id="iwq-settings-form">
					<?php settings_fields( IWQ_Settings::OPTION_GROUP . '_' . $current ); ?>

					<?php foreach ( $sections as $iwq_section ) : ?>
						<section class="iwq-card">
							<div class="iwq-card__header">
								<div>
									<h3 class="iwq-card__title"><?php echo esc_html( $iwq_section['title'] ); ?></h3>
									<?php if ( ! empty( $iwq_section['desc'] ) ) : ?>
										<p class="iwq-card__subtitle"><?php echo esc_html( $iwq_section['desc'] ); ?></p>
									<?php endif; ?>
								</div>
							</div>

							<div class="iwq-card__body">
								<?php
								foreach ( $iwq_section['fields'] as $iwq_key => $iwq_field ) {
									IWQ_Settings_Fields::render( $iwq_key, $iwq_field );
								}
								?>
							</div>
						</section>
					<?php endforeach; ?>

					<div class="iwq-savebar">
						<span class="iwq-savebar__status" role="status"><?php esc_html_e( 'Sin cambios pendientes', 'imagina-woo-quotes' ); ?></span>
						<span class="iwq-savebar__hint"><kbd>Ctrl</kbd> + <kbd>S</kbd></span>
						<div class="iwq-savebar__actions">
							<button type="submit" class="iwq-btn iwq-btn--primary">
								<?php echo IWQ_Admin::icon( 'save' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Guardar cambios', 'imagina-woo-quotes' ); ?>
							</button>
						</div>
					</div>
				</form>

			<?php endif; ?>

		</main>
	</div>

	<div class="iwq-toast" role="status" aria-live="polite" hidden></div>
</div>
