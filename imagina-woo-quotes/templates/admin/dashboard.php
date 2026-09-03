<?php
/**
 * Pestaña de inicio.
 *
 * @package ImaginaWooQuotes
 *
 * @var array      $data      Métricas de los últimos 30 días.
 * @var array      $checklist Comprobaciones de puesta en marcha.
 * @var WC_Order[] $recent    Últimas solicitudes.
 */

defined( 'ABSPATH' ) || exit;

$iwq_pending = $data['counts']['iwq-new'];
$iwq_done    = count( array_filter( array_column( $checklist, 'ok' ) ) );
$iwq_icon    = array( IWQ_Admin::class, 'icon' );
?>
<div class="iwq-kpis">
	<a class="iwq-kpi iwq-kpi--accent" href="<?php echo esc_url( IWQ_Dashboard::get_orders_url() ); ?>">
		<span class="iwq-kpi__label"><?php esc_html_e( 'Solicitudes en 30 días', 'imagina-woo-quotes' ); ?></span>
		<span class="iwq-kpi__value"><?php echo esc_html( number_format_i18n( $data['total'] ) ); ?></span>
		<span class="iwq-kpi__help"><?php esc_html_e( 'Ver todos los presupuestos', 'imagina-woo-quotes' ); ?></span>
	</a>

	<a class="iwq-kpi iwq-kpi--warning" href="<?php echo esc_url( IWQ_Dashboard::get_orders_url( 'iwq-new' ) ); ?>">
		<span class="iwq-kpi__label"><?php esc_html_e( 'Esperan respuesta', 'imagina-woo-quotes' ); ?></span>
		<span class="iwq-kpi__value"><?php echo esc_html( number_format_i18n( $iwq_pending ) ); ?></span>
		<span class="iwq-kpi__help"><?php esc_html_e( 'Solicitudes nuevas sin presupuesto enviado', 'imagina-woo-quotes' ); ?></span>
	</a>

	<a class="iwq-kpi iwq-kpi--success" href="<?php echo esc_url( IWQ_Dashboard::get_orders_url( 'iwq-accepted' ) ); ?>">
		<span class="iwq-kpi__label"><?php esc_html_e( 'Aceptados', 'imagina-woo-quotes' ); ?></span>
		<span class="iwq-kpi__value"><?php echo esc_html( number_format_i18n( $data['accepted'] ) ); ?></span>
		<span class="iwq-kpi__help"><?php echo esc_html( sprintf( /* translators: %s: percentage */ __( '%s%% de tasa de aceptación', 'imagina-woo-quotes' ), number_format_i18n( $data['accept_rate'], 1 ) ) ); ?></span>
	</a>

	<div class="iwq-kpi">
		<span class="iwq-kpi__label"><?php esc_html_e( 'Valor aceptado', 'imagina-woo-quotes' ); ?></span>
		<span class="iwq-kpi__value"><?php echo wp_kses_post( wc_price( $data['value'] ) ); ?></span>
		<span class="iwq-kpi__help"><?php esc_html_e( 'Suma de los presupuestos aceptados', 'imagina-woo-quotes' ); ?></span>
	</div>
</div>

<div class="iwq-grid-2">

	<section class="iwq-card">
		<div class="iwq-card__header">
			<div>
				<h3 class="iwq-card__title"><?php esc_html_e( 'Puesta en marcha', 'imagina-woo-quotes' ); ?></h3>
				<p class="iwq-card__subtitle">
					<?php echo esc_html( sprintf( /* translators: 1: done, 2: total */ __( '%1$d de %2$d pasos completados', 'imagina-woo-quotes' ), $iwq_done, count( $checklist ) ) ); ?>
				</p>
			</div>
			<span class="iwq-progress" aria-hidden="true"><span style="width:<?php echo esc_attr( round( $iwq_done / max( 1, count( $checklist ) ) * 100 ) ); ?>%"></span></span>
		</div>

		<div class="iwq-card__body">
			<ul class="iwq-checklist">
				<?php foreach ( $checklist as $iwq_item ) : ?>
					<li>
						<span class="iwq-checklist__icon <?php echo $iwq_item['ok'] ? 'iwq-checklist__icon--ok' : 'iwq-checklist__icon--todo'; ?>">
							<?php echo $iwq_item['ok'] ? '&#10003;' : '&bull;'; ?>
						</span>
						<span class="iwq-checklist__text">
							<?php echo esc_html( $iwq_item['label'] ); ?>
							<small>
								<?php echo esc_html( $iwq_item['help'] ); ?>
								<?php if ( ! $iwq_item['ok'] ) : ?>
									<a href="<?php echo esc_url( $iwq_item['url'] ); ?>"><?php echo esc_html( $iwq_item['action'] ); ?> &rarr;</a>
								<?php endif; ?>
							</small>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<section class="iwq-card">
		<div class="iwq-card__header">
			<div>
				<h3 class="iwq-card__title"><?php esc_html_e( 'Accesos rápidos', 'imagina-woo-quotes' ); ?></h3>
				<p class="iwq-card__subtitle"><?php esc_html_e( 'Lo que más se toca en el día a día.', 'imagina-woo-quotes' ); ?></p>
			</div>
		</div>

		<div class="iwq-card__body">
			<div class="iwq-quicklinks">
				<a class="iwq-quicklink" href="<?php echo esc_url( IWQ_Dashboard::get_orders_url( 'iwq-new' ) ); ?>">
					<?php echo call_user_func( $iwq_icon, 'orders' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Solicitudes nuevas', 'imagina-woo-quotes' ); ?>
					<small><?php esc_html_e( 'Responder y enviar precio', 'imagina-woo-quotes' ); ?></small>
				</a>

				<a class="iwq-quicklink" href="<?php echo esc_url( admin_url( 'admin.php?page=iwq-settings&tab=preview' ) ); ?>">
					<?php echo call_user_func( $iwq_icon, 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Vista previa', 'imagina-woo-quotes' ); ?>
					<small><?php esc_html_e( 'Emails y PDF como los ve el cliente', 'imagina-woo-quotes' ); ?></small>
				</a>

				<a class="iwq-quicklink" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . IWQ_PDF_Template_CPT::POST_TYPE ) ); ?>">
					<?php echo call_user_func( $iwq_icon, 'blocks' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Plantillas de PDF', 'imagina-woo-quotes' ); ?>
					<small><?php esc_html_e( 'Editar con bloques', 'imagina-woo-quotes' ); ?></small>
				</a>

				<a class="iwq-quicklink" href="<?php echo esc_url( admin_url( 'admin.php?page=iwq-settings&tab=form' ) ); ?>">
					<?php echo call_user_func( $iwq_icon, 'form' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Formulario', 'imagina-woo-quotes' ); ?>
					<small><?php esc_html_e( 'Campos que rellena el cliente', 'imagina-woo-quotes' ); ?></small>
				</a>

				<a class="iwq-quicklink" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email' ) ); ?>">
					<?php echo call_user_func( $iwq_icon, 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Emails de WooCommerce', 'imagina-woo-quotes' ); ?>
					<small><?php esc_html_e( 'Activar, asuntos y destinatarios', 'imagina-woo-quotes' ); ?></small>
				</a>

				<a class="iwq-quicklink" href="<?php echo esc_url( admin_url( 'admin.php?page=iwq-settings&tab=rules' ) ); ?>">
					<?php echo call_user_func( $iwq_icon, 'filter' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Reglas', 'imagina-woo-quotes' ); ?>
					<small><?php esc_html_e( 'Qué productos admiten presupuesto', 'imagina-woo-quotes' ); ?></small>
				</a>
			</div>
		</div>
	</section>

</div>

<section class="iwq-card">
	<div class="iwq-card__header">
		<div>
			<h3 class="iwq-card__title"><?php esc_html_e( 'Actividad reciente', 'imagina-woo-quotes' ); ?></h3>
			<p class="iwq-card__subtitle"><?php esc_html_e( 'Las últimas solicitudes recibidas.', 'imagina-woo-quotes' ); ?></p>
		</div>
		<a class="iwq-btn iwq-btn--secondary iwq-btn--sm" href="<?php echo esc_url( IWQ_Dashboard::get_orders_url() ); ?>"><?php esc_html_e( 'Ver todas', 'imagina-woo-quotes' ); ?></a>
	</div>

	<div class="iwq-card__body iwq-card__body--flush">
		<?php if ( $recent ) : ?>
			<div class="iwq-table-wrap">
				<table class="iwq-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Presupuesto', 'imagina-woo-quotes' ); ?></th>
							<th><?php esc_html_e( 'Cliente', 'imagina-woo-quotes' ); ?></th>
							<th><?php esc_html_e( 'Estado', 'imagina-woo-quotes' ); ?></th>
							<th><?php esc_html_e( 'Fecha', 'imagina-woo-quotes' ); ?></th>
							<th class="iwq-numeric"><?php esc_html_e( 'Total', 'imagina-woo-quotes' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent as $iwq_order ) : ?>
							<tr>
								<td><a href="<?php echo esc_url( $iwq_order->get_edit_order_url() ); ?>">#<?php echo esc_html( $iwq_order->get_order_number() ); ?></a></td>
								<td>
									<?php echo esc_html( $iwq_order->get_formatted_billing_full_name() ? $iwq_order->get_formatted_billing_full_name() : __( 'Sin nombre', 'imagina-woo-quotes' ) ); ?>
									<span class="iwq-muted"><?php echo esc_html( $iwq_order->get_billing_email() ); ?></span>
								</td>
								<td>
									<span class="iwq-badge" style="--iwq-badge:<?php echo esc_attr( iwq_get_status_color( $iwq_order->get_status() ) ); ?>">
										<?php echo esc_html( iwq_get_status_label( $iwq_order->get_status() ) ); ?>
									</span>
								</td>
								<td>
									<?php
									$iwq_created = $iwq_order->get_date_created();
									echo esc_html( $iwq_created ? sprintf( /* translators: %s: human time diff */ __( 'hace %s', 'imagina-woo-quotes' ), human_time_diff( $iwq_created->getTimestamp() ) ) : '—' );
									?>
								</td>
								<td class="iwq-numeric"><?php echo wp_kses_post( $iwq_order->get_formatted_order_total() ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="iwq-empty"><?php esc_html_e( 'Todavía no ha llegado ninguna solicitud. Cuando un cliente pida presupuesto aparecerá aquí.', 'imagina-woo-quotes' ); ?></p>
		<?php endif; ?>
	</div>
</section>
