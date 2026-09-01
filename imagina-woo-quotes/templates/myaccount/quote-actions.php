<?php
/**
 * Botones de respuesta e hilo de negociación en el detalle del presupuesto.
 *
 * @package ImaginaWooQuotes
 *
 * @var WC_Order  $order Pedido.
 * @var IWQ_Quote $quote Presupuesto.
 */

defined( 'ABSPATH' ) || exit;

$iwq_thread = $quote->get_negotiation_thread();
?>
<section class="iwq iwq-quote-response">

	<?php if ( $quote->get_expiry_date() ) : ?>
		<p class="iwq-expiry-notice">
			<?php
			$iwq_days = $quote->get_days_to_expiry();

			if ( $quote->is_expired() ) {
				esc_html_e( 'Este presupuesto ha vencido.', 'imagina-woo-quotes' );
			} else {
				printf(
					esc_html(
						/* translators: %s: número de días. */
						_n( 'Válido durante %s día más.', 'Válido durante %s días más.', $iwq_days, 'imagina-woo-quotes' )
					),
					esc_html( number_format_i18n( $iwq_days ) )
				);
			}
			?>
		</p>
	<?php endif; ?>

	<?php if ( $quote->is_actionable() ) : ?>
		<div class="iwq-quote-actions">
			<a class="iwq-add-button" href="<?php echo esc_url( $quote->get_accept_url() ); ?>">
				<?php esc_html_e( 'Aceptar el presupuesto', 'imagina-woo-quotes' ); ?>
			</a>

			<a class="iwq-add-button iwq-add-button--reject" href="<?php echo esc_url( $quote->get_reject_url() ); ?>">
				<?php esc_html_e( 'Rechazarlo', 'imagina-woo-quotes' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<?php if ( $iwq_thread ) : ?>
		<h3><?php esc_html_e( 'Conversación', 'imagina-woo-quotes' ); ?></h3>

		<div class="iwq-negotiation">
			<?php foreach ( $iwq_thread as $iwq_entry ) : ?>
				<div class="iwq-negotiation__entry iwq-negotiation__entry--<?php echo esc_attr( $iwq_entry['author'] ); ?>">
					<?php if ( ! empty( $iwq_entry['offer'] ) ) : ?>
						<p><strong>
							<?php
							printf(
								/* translators: %s: importe propuesto. */
								esc_html__( 'Propuesta: %s', 'imagina-woo-quotes' ),
								wp_kses_post( wc_price( $iwq_entry['offer'], array( 'currency' => $order->get_currency() ) ) )
							);
							?>
						</strong></p>
					<?php endif; ?>

					<?php if ( ! empty( $iwq_entry['message'] ) ) : ?>
						<p><?php echo esc_html( $iwq_entry['message'] ); ?></p>
					<?php endif; ?>

					<p class="iwq-negotiation__meta">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: autor, 2: fecha. */
								__( '%1$s · %2$s', 'imagina-woo-quotes' ),
								'customer' === $iwq_entry['author']
									? __( 'Tú', 'imagina-woo-quotes' )
									: get_bloginfo( 'name' ),
								date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $iwq_entry['date'] )
							)
						);
						?>
					</p>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( $quote->is_actionable() && iwq_option_enabled( 'allow_counter_offers', true ) ) : ?>
		<details class="iwq-counter-offer">
			<summary><?php esc_html_e( '¿Quieres proponer otro precio?', 'imagina-woo-quotes' ); ?></summary>

			<form class="iwq-counter-offer-form" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
				<div class="iwq-field iwq-field--half">
					<label class="iwq-field__label" for="iwq-offer">
						<?php esc_html_e( 'Tu propuesta', 'imagina-woo-quotes' ); ?>
					</label>

					<input
						type="number"
						class="iwq-field__control"
						id="iwq-offer"
						name="iwq_offer"
						step="0.01"
						min="0"
						required
					>
				</div>

				<div class="iwq-field">
					<label class="iwq-field__label" for="iwq-offer-message">
						<?php esc_html_e( 'Comentario (opcional)', 'imagina-woo-quotes' ); ?>
					</label>

					<textarea class="iwq-field__control" id="iwq-offer-message" name="iwq_message" rows="3"></textarea>
				</div>

				<button type="submit" class="iwq-add-button">
					<?php esc_html_e( 'Enviar propuesta', 'imagina-woo-quotes' ); ?>
				</button>
			</form>
		</details>
	<?php endif; ?>

</section>
