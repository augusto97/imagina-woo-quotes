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

	<div class="iwq-stats__periods">
		<?php foreach ( $iwq_periods as $iwq_value => $iwq_label ) : ?>
			<a
				class="button<?php echo $iwq_value === $days ? ' button-primary' : ''; ?>"
				href="<?php echo esc_url( admin_url( 'admin.php?page=iwq-settings&tab=stats&days=' . $iwq_value ) ); ?>"
			>
				<?php echo esc_html( $iwq_label ); ?>
			</a>
		<?php endforeach; ?>
	</div>

	<div class="iwq-stats__cards">
		<div class="iwq-stat">
			<span class="iwq-stat__value"><?php echo esc_html( number_format_i18n( $data['total'] ) ); ?></span>
			<span class="iwq-stat__label"><?php esc_html_e( 'Solicitudes', 'imagina-woo-quotes' ); ?></span>
		</div>

		<div class="iwq-stat">
			<span class="iwq-stat__value"><?php echo esc_html( number_format_i18n( $data['accepted'] ) ); ?></span>
			<span class="iwq-stat__label"><?php esc_html_e( 'Aceptados', 'imagina-woo-quotes' ); ?></span>
		</div>

		<div class="iwq-stat">
			<span class="iwq-stat__value"><?php echo esc_html( number_format_i18n( $data['accept_rate'], 1 ) ); ?>%</span>
			<span class="iwq-stat__label"><?php esc_html_e( 'Tasa de aceptación', 'imagina-woo-quotes' ); ?></span>
			<span class="iwq-stat__help"><?php esc_html_e( 'Sobre los presupuestos que el cliente respondió.', 'imagina-woo-quotes' ); ?></span>
		</div>

		<div class="iwq-stat">
			<span class="iwq-stat__value"><?php echo esc_html( number_format_i18n( $data['response_rate'], 1 ) ); ?>%</span>
			<span class="iwq-stat__label"><?php esc_html_e( 'Tasa de respuesta', 'imagina-woo-quotes' ); ?></span>
		</div>

		<div class="iwq-stat">
			<span class="iwq-stat__value"><?php echo wp_kses_post( wc_price( $data['value'] ) ); ?></span>
			<span class="iwq-stat__label"><?php esc_html_e( 'Valor aceptado', 'imagina-woo-quotes' ); ?></span>
		</div>
	</div>

	<h2><?php esc_html_e( 'Reparto por estado', 'imagina-woo-quotes' ); ?></h2>

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
		<p><?php esc_html_e( 'Todavía no hay presupuestos en este periodo.', 'imagina-woo-quotes' ); ?></p>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Productos más solicitados', 'imagina-woo-quotes' ); ?></h2>

	<p class="description">
		<?php esc_html_e( 'Cuántas veces se ha pedido presupuesto de cada producto, desde que instalaste el plugin. Un producto muy solicitado y poco vendido suele ser una señal de que su precio no está claro.', 'imagina-woo-quotes' ); ?>
	</p>

	<?php if ( $data['top'] ) : ?>
		<table class="widefat striped iwq-top-products">
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
	<?php else : ?>
		<p><?php esc_html_e( 'Todavía no hay datos.', 'imagina-woo-quotes' ); ?></p>
	<?php endif; ?>

</div>
