<?php
/**
 * Pestaña de estadísticas.
 *
 * @package ImaginaWooQuotes
 *
 * @var array $data Métricas calculadas.
 * @var int   $days Periodo en días.
 */

defined( 'ABSPATH' ) || exit;

$iwq_periods = array(
	7   => __( '7 días', 'imagina-woo-quotes' ),
	30  => __( '30 días', 'imagina-woo-quotes' ),
	90  => __( '90 días', 'imagina-woo-quotes' ),
	365 => __( 'Un año', 'imagina-woo-quotes' ),
);
?>
<div class="iwq-stats">

	<nav class="iwq-stats__periods" aria-label="<?php esc_attr_e( 'Periodo', 'imagina-woo-quotes' ); ?>">
		<?php foreach ( $iwq_periods as $iwq_value => $iwq_label ) : ?>
			<a
				class="<?php echo $iwq_value === $days ? 'is-active' : ''; ?>"
				href="<?php echo esc_url( admin_url( 'admin.php?page=iwq-settings&tab=stats&days=' . $iwq_value ) ); ?>"
				<?php echo $iwq_value === $days ? 'aria-current="page"' : ''; ?>
			>
				<?php echo esc_html( $iwq_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="iwq-kpis">
		<div class="iwq-kpi iwq-kpi--accent">
			<span class="iwq-kpi__label"><?php esc_html_e( 'Solicitudes', 'imagina-woo-quotes' ); ?></span>
			<span class="iwq-kpi__value"><?php echo esc_html( number_format_i18n( $data['total'] ) ); ?></span>
		</div>

		<div class="iwq-kpi iwq-kpi--success">
			<span class="iwq-kpi__label"><?php esc_html_e( 'Aceptados', 'imagina-woo-quotes' ); ?></span>
			<span class="iwq-kpi__value"><?php echo esc_html( number_format_i18n( $data['accepted'] ) ); ?></span>
		</div>

		<div class="iwq-kpi">
			<span class="iwq-kpi__label"><?php esc_html_e( 'Tasa de aceptación', 'imagina-woo-quotes' ); ?></span>
			<span class="iwq-kpi__value"><?php echo esc_html( number_format_i18n( $data['accept_rate'], 1 ) ); ?>%</span>
			<span class="iwq-kpi__help"><?php esc_html_e( 'Sobre los presupuestos que el cliente respondió.', 'imagina-woo-quotes' ); ?></span>
		</div>

		<div class="iwq-kpi">
			<span class="iwq-kpi__label"><?php esc_html_e( 'Tasa de respuesta', 'imagina-woo-quotes' ); ?></span>
			<span class="iwq-kpi__value"><?php echo esc_html( number_format_i18n( $data['response_rate'], 1 ) ); ?>%</span>
		</div>

		<div class="iwq-kpi">
			<span class="iwq-kpi__label"><?php esc_html_e( 'Valor aceptado', 'imagina-woo-quotes' ); ?></span>
			<span class="iwq-kpi__value"><?php echo wp_kses_post( wc_price( $data['value'] ) ); ?></span>
		</div>
	</div>

	<div class="iwq-grid-2">

		<section class="iwq-card">
			<div class="iwq-card__header">
				<div>
					<h3 class="iwq-card__title"><?php esc_html_e( 'Reparto por estado', 'imagina-woo-quotes' ); ?></h3>
					<p class="iwq-card__subtitle"><?php esc_html_e( 'En qué punto del ciclo está cada solicitud del periodo.', 'imagina-woo-quotes' ); ?></p>
				</div>
			</div>

			<div class="iwq-card__body">
				<?php if ( $data['total'] ) : ?>
					<div class="iwq-bars">
						<?php foreach ( $data['counts'] as $iwq_status => $iwq_count ) : ?>
							<?php $iwq_percent = round( $iwq_count / $data['total'] * 100, 1 ); ?>

							<div class="iwq-bar">
								<span class="iwq-bar__label"><?php echo esc_html( iwq_get_status_label( $iwq_status ) ); ?></span>

								<span class="iwq-bar__track">
									<span
										class="iwq-bar__fill"
										style="width:<?php echo esc_attr( $iwq_percent ); ?>%;background:<?php echo esc_attr( iwq_get_status_color( $iwq_status ) ); ?>"
									></span>
								</span>

								<span class="iwq-bar__value">
									<?php echo esc_html( number_format_i18n( $iwq_count ) ); ?>
									<span class="iwq-muted">(<?php echo esc_html( number_format_i18n( $iwq_percent, 1 ) ); ?>%)</span>
								</span>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p class="iwq-empty"><?php esc_html_e( 'Todavía no hay presupuestos en este periodo.', 'imagina-woo-quotes' ); ?></p>
				<?php endif; ?>
			</div>
		</section>

		<section class="iwq-card">
			<div class="iwq-card__header">
				<div>
					<h3 class="iwq-card__title"><?php esc_html_e( 'Productos más solicitados', 'imagina-woo-quotes' ); ?></h3>
					<p class="iwq-card__subtitle"><?php esc_html_e( 'Acumulado desde que instalaste el plugin. Un producto muy solicitado y poco vendido suele avisar de que su precio no está claro.', 'imagina-woo-quotes' ); ?></p>
				</div>
			</div>

			<div class="iwq-card__body iwq-card__body--flush">
				<?php if ( $data['top'] ) : ?>
					<div class="iwq-table-wrap">
						<table class="iwq-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Producto', 'imagina-woo-quotes' ); ?></th>
									<th class="iwq-numeric"><?php esc_html_e( 'Solicitudes', 'imagina-woo-quotes' ); ?></th>
								</tr>
							</thead>

							<tbody>
								<?php foreach ( $data['top'] as $iwq_product ) : ?>
									<tr>
										<td>
											<a href="<?php echo esc_url( get_edit_post_link( $iwq_product['id'] ) ); ?>">
												<?php echo esc_html( $iwq_product['name'] ); ?>
											</a>
										</td>
										<td class="iwq-numeric"><?php echo esc_html( number_format_i18n( $iwq_product['count'] ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<p class="iwq-empty"><?php esc_html_e( 'Todavía no hay datos.', 'imagina-woo-quotes' ); ?></p>
				<?php endif; ?>
			</div>
		</section>

	</div>

</div>
