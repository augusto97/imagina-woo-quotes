<?php
/**
 * Panel del presupuesto en la pantalla del pedido.
 *
 * @package ImaginaWooQuotes
 *
 * @var WC_Order  $order Pedido.
 * @var IWQ_Quote $quote Presupuesto.
 */

defined( 'ABSPATH' ) || exit;

$iwq_expiry = $quote->get_expiry_date();
$iwq_thread = $quote->get_negotiation_thread();
$iwq_offer  = $quote->get_latest_counter_offer();
?>
<div class="iwq-order-panel">

	<?php if ( $iwq_offer ) : ?>
		<div class="iwq-order-panel__alert">
			<strong><?php esc_html_e( 'Contraoferta pendiente', 'imagina-woo-quotes' ); ?></strong>

			<p>
				<?php
				printf(
					/* translators: 1: importe propuesto, 2: importe del presupuesto. */
					esc_html__( 'El cliente propone %1$s frente a los %2$s del presupuesto.', 'imagina-woo-quotes' ),
					wp_kses_post( wc_price( $iwq_offer['offer'], array( 'currency' => $order->get_currency() ) ) ),
					wp_kses_post( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) )
				);
				?>
			</p>

			<?php if ( ! empty( $iwq_offer['message'] ) ) : ?>
				<blockquote><?php echo esc_html( $iwq_offer['message'] ); ?></blockquote>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<p class="form-field">
		<label for="iwq_expiry_date"><?php esc_html_e( 'Vence el', 'imagina-woo-quotes' ); ?></label>

		<input
			type="date"
			id="iwq_expiry_date"
			name="iwq_expiry_date"
			value="<?php echo esc_attr( $iwq_expiry ? gmdate( 'Y-m-d', $iwq_expiry ) : '' ); ?>"
			class="widefat"
		>

		<span class="description"><?php esc_html_e( 'Déjalo vacío para que no venza.', 'imagina-woo-quotes' ); ?></span>
	</p>

	<?php if ( IWQ_PDF::is_available() ) : ?>
		<p class="form-field">
			<label for="iwq_pdf_template_id"><?php esc_html_e( 'Plantilla de PDF', 'imagina-woo-quotes' ); ?></label>

			<select id="iwq_pdf_template_id" name="iwq_pdf_template_id" class="widefat">
				<option value="0"><?php esc_html_e( '— La configurada por defecto —', 'imagina-woo-quotes' ); ?></option>

				<?php foreach ( IWQ_PDF_Template_CPT::get_choices() as $iwq_id => $iwq_title ) : ?>
					<option value="<?php echo esc_attr( $iwq_id ); ?>" <?php selected( (int) $order->get_meta( '_iwq_pdf_template_id' ), $iwq_id ); ?>>
						<?php echo esc_html( $iwq_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<p>
			<a class="button" href="<?php echo esc_url( IWQ_PDF::get_download_url( $order ) ); ?>" target="_blank" rel="noopener">
				<?php esc_html_e( 'Ver el PDF', 'imagina-woo-quotes' ); ?>
			</a>
		</p>
	<?php endif; ?>

	<?php if ( $quote->can_transition_to( 'iwq-pending' ) ) : ?>
		<div class="iwq-order-panel__hint">
			<?php esc_html_e( 'Pon los precios en las líneas del pedido y usa la acción «Enviar el presupuesto al cliente» para mandárselo.', 'imagina-woo-quotes' ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $iwq_thread ) : ?>
		<h4><?php esc_html_e( 'Conversación', 'imagina-woo-quotes' ); ?></h4>

		<ul class="iwq-order-panel__thread">
			<?php foreach ( $iwq_thread as $iwq_entry ) : ?>
				<li class="iwq-order-panel__entry iwq-order-panel__entry--<?php echo esc_attr( $iwq_entry['author'] ); ?>">
					<?php if ( ! empty( $iwq_entry['offer'] ) ) : ?>
						<strong><?php echo wp_kses_post( wc_price( $iwq_entry['offer'], array( 'currency' => $order->get_currency() ) ) ); ?></strong>
					<?php endif; ?>

					<?php if ( ! empty( $iwq_entry['message'] ) ) : ?>
						<p><?php echo esc_html( $iwq_entry['message'] ); ?></p>
					<?php endif; ?>

					<span class="iwq-muted">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: autor, 2: fecha. */
								__( '%1$s · %2$s', 'imagina-woo-quotes' ),
								'customer' === $iwq_entry['author']
									? __( 'Cliente', 'imagina-woo-quotes' )
									: __( 'Tienda', 'imagina-woo-quotes' ),
								date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $iwq_entry['date'] )
							)
						);
						?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( 'iwq-pending' === $order->get_status() ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="iwq-order-panel__reply">
			<?php wp_nonce_field( 'iwq_admin_offer' ); ?>
			<input type="hidden" name="action" value="iwq_admin_offer">
			<input type="hidden" name="order_id" value="<?php echo esc_attr( $order->get_id() ); ?>">

			<label for="iwq_admin_message" class="screen-reader-text">
				<?php esc_html_e( 'Responder al cliente', 'imagina-woo-quotes' ); ?>
			</label>

			<textarea
				id="iwq_admin_message"
				name="iwq_message"
				rows="3"
				class="widefat"
				placeholder="<?php esc_attr_e( 'Responder al cliente…', 'imagina-woo-quotes' ); ?>"
			></textarea>

			<button type="submit" class="button"><?php esc_html_e( 'Añadir a la conversación', 'imagina-woo-quotes' ); ?></button>
		</form>
	<?php endif; ?>

</div>
