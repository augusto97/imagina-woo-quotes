<?php
/**
 * Pestaña de vista previa de emails y PDF.
 *
 * @package ImaginaWooQuotes
 *
 * @var array  $emails Emails del plugin.
 * @var array  $quotes Presupuestos recientes.
 * @var string $nonce  Nonce de las peticiones.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="iwq-preview" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">

	<p class="description">
		<?php esc_html_e( 'Cada email se muestra exactamente como lo recibe su destinatario: mismo asunto, mismo contenido, mismos adjuntos, con el diseño elegido en la pestaña Emails. Los datos salen del presupuesto que elijas.', 'imagina-woo-quotes' ); ?>
	</p>

	<div class="iwq-preview__controls">
		<label>
			<span><?php esc_html_e( 'Email', 'imagina-woo-quotes' ); ?></span>
			<select id="iwq-preview-email">
				<optgroup label="<?php esc_attr_e( 'Al cliente', 'imagina-woo-quotes' ); ?>">
					<?php foreach ( $emails as $iwq_id => $iwq_email ) : ?>
						<?php if ( 'customer' === $iwq_email['to'] ) : ?>
							<option value="<?php echo esc_attr( $iwq_id ); ?>"><?php echo esc_html( $iwq_email['title'] ); ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</optgroup>
				<optgroup label="<?php esc_attr_e( 'Al administrador', 'imagina-woo-quotes' ); ?>">
					<?php foreach ( $emails as $iwq_id => $iwq_email ) : ?>
						<?php if ( 'admin' === $iwq_email['to'] ) : ?>
							<option value="<?php echo esc_attr( $iwq_id ); ?>"><?php echo esc_html( $iwq_email['title'] ); ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</optgroup>
			</select>
		</label>

		<label>
			<span><?php esc_html_e( 'Presupuesto', 'imagina-woo-quotes' ); ?></span>
			<select id="iwq-preview-order">
				<?php if ( empty( $quotes ) ) : ?>
					<option value=""><?php esc_html_e( '— No hay presupuestos todavía —', 'imagina-woo-quotes' ); ?></option>
				<?php endif; ?>
				<?php foreach ( $quotes as $iwq_id => $iwq_label ) : ?>
					<option value="<?php echo esc_attr( $iwq_id ); ?>"><?php echo esc_html( $iwq_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>

		<button type="button" class="button" id="iwq-preview-sample"><?php esc_html_e( 'Crear presupuesto de ejemplo', 'imagina-woo-quotes' ); ?></button>

		<span class="iwq-preview__view">
			<button type="button" class="button button-primary is-active" data-view="html"><?php esc_html_e( 'Email', 'imagina-woo-quotes' ); ?></button>
			<button type="button" class="button" data-view="plain"><?php esc_html_e( 'Texto plano', 'imagina-woo-quotes' ); ?></button>
			<button type="button" class="button" data-view="pdf"><?php esc_html_e( 'PDF adjunto', 'imagina-woo-quotes' ); ?></button>
		</span>
	</div>

	<div class="iwq-preview__meta" id="iwq-preview-meta" hidden>
		<div><strong><?php esc_html_e( 'Asunto', 'imagina-woo-quotes' ); ?></strong> <span data-meta="subject"></span></div>
		<div><strong><?php esc_html_e( 'De', 'imagina-woo-quotes' ); ?></strong> <span data-meta="from"></span></div>
		<div><strong><?php esc_html_e( 'Para', 'imagina-woo-quotes' ); ?></strong> <span data-meta="to"></span></div>
		<div><strong><?php esc_html_e( 'Adjuntos', 'imagina-woo-quotes' ); ?></strong> <span data-meta="attachments"></span></div>
		<div><strong><?php esc_html_e( 'Estado del presupuesto', 'imagina-woo-quotes' ); ?></strong> <span data-meta="status"></span></div>
		<div class="iwq-preview__disabled" data-meta="disabled" hidden><?php esc_html_e( 'Este email está desactivado en WooCommerce → Ajustes → Emails: no se enviará.', 'imagina-woo-quotes' ); ?></div>
	</div>

	<div class="iwq-preview__frame">
		<iframe id="iwq-preview-iframe" title="<?php esc_attr_e( 'Vista previa', 'imagina-woo-quotes' ); ?>" sandbox="allow-same-origin"></iframe>
	</div>

	<div class="iwq-preview__test">
		<label for="iwq-preview-to"><?php esc_html_e( 'Enviar una prueba a', 'imagina-woo-quotes' ); ?></label>
		<input type="email" id="iwq-preview-to" class="regular-text" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>">
		<button type="button" class="button" id="iwq-preview-send"><?php esc_html_e( 'Enviar prueba', 'imagina-woo-quotes' ); ?></button>
		<span class="iwq-preview__result" id="iwq-preview-result" role="status"></span>
	</div>

</div>
