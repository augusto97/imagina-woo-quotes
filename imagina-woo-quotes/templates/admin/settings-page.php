<?php
/**
 * Página de ajustes.
 *
 * @package ImaginaWooQuotes
 *
 * @var array  $tabs     Pestañas disponibles.
 * @var string $current  Pestaña activa.
 * @var array  $sections Secciones de la pestaña activa.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap iwq-admin">

	<h1><?php esc_html_e( 'Presupuestos', 'imagina-woo-quotes' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $tabs as $iwq_slug => $iwq_label ) : ?>
			<a
				class="nav-tab<?php echo $iwq_slug === $current ? ' nav-tab-active' : ''; ?>"
				href="<?php echo esc_url( admin_url( 'admin.php?page=iwq-settings&tab=' . $iwq_slug ) ); ?>"
			>
				<?php echo esc_html( $iwq_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php if ( 'stats' === $current ) : ?>

		<?php IWQ_Statistics::render(); ?>

	<?php else : ?>

		<?php if ( 'pdf' === $current && ! IWQ_PDF::is_available() ) : ?>
			<div class="notice notice-warning">
				<p>
					<?php esc_html_e( 'La librería de PDF no está instalada. Ejecuta «composer install --no-dev» en el directorio del plugin para activar la generación de documentos.', 'imagina-woo-quotes' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<form method="post" action="options.php" class="iwq-settings-form">
			<?php settings_fields( IWQ_Settings::OPTION_GROUP . '_' . $current ); ?>

			<?php foreach ( $sections as $iwq_section ) : ?>
				<h2><?php echo esc_html( $iwq_section['title'] ); ?></h2>

				<table class="form-table" role="presentation">
					<tbody>
						<?php
						foreach ( $iwq_section['fields'] as $iwq_key => $iwq_field ) {
							IWQ_Settings_Fields::render( $iwq_key, $iwq_field );
						}
						?>
					</tbody>
				</table>
			<?php endforeach; ?>

			<?php submit_button(); ?>
		</form>

	<?php endif; ?>

</div>
