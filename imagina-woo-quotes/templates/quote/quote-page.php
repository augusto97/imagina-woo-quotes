<?php
/**
 * Página de la lista de presupuesto con el formulario de solicitud.
 *
 * @package ImaginaWooQuotes
 *
 * @var array $items Líneas de la lista.
 */

defined( 'ABSPATH' ) || exit;

$iwq_is_empty     = empty( $items );
$iwq_page_classes = array_merge( array( 'iwq', 'iwq-quote-page' ), IWQ_Design::get_page_classes() );
$iwq_list_title   = IWQ_Design::get( 'page_list_title' );
$iwq_show_form    = ! $iwq_is_empty || iwq_option_enabled( 'show_form_when_empty' );
?>
<div class="<?php echo esc_attr( implode( ' ', $iwq_page_classes ) ); ?>">

	<?php
	// Los avisos de aceptar, rechazar o enlace caducado llegan aquí por la
	// URL del email. En un tema de bloques nadie los imprime en una página
	// normal, así que lo hacemos nosotros, igual que hace el carrito.
	if ( function_exists( 'wc_print_notices' ) ) {
		wc_print_notices();
	}
	?>

	<?php if ( $iwq_is_empty ) : ?>

		<div class="iwq-empty">
			<p><?php esc_html_e( 'Todavía no has añadido ningún producto a tu presupuesto.', 'imagina-woo-quotes' ); ?></p>

			<a class="iwq-add-button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<?php esc_html_e( 'Ver el catálogo', 'imagina-woo-quotes' ); ?>
			</a>
		</div>

	<?php endif; ?>

	<div class="iwq-quote-page__layout">

	<?php if ( ! $iwq_is_empty ) : ?>

		<div class="iwq-quote-page__list">
			<h2 class="iwq-quote-page__title"><?php echo esc_html( $iwq_list_title ? $iwq_list_title : __( 'Productos en tu solicitud', 'imagina-woo-quotes' ) ); ?></h2>

			<?php iwq_get_template( 'quote/drawer-content.php', array( 'items' => $items ) ); ?>

			<p class="iwq-quote-page__tools">
				<button type="button" class="iwq-clear-list iwq-link-button">
					<?php esc_html_e( 'Vaciar la lista', 'imagina-woo-quotes' ); ?>
				</button>
			</p>
		</div>

	<?php endif; ?>

	<?php if ( $iwq_show_form ) : ?>

		<div class="iwq-quote-page__form">
			<h2><?php echo esc_html( iwq_get_option( 'form_title', __( 'Cuéntanos qué necesitas', 'imagina-woo-quotes' ) ) ); ?></h2>

			<form class="iwq-request-form" method="post" enctype="multipart/form-data" novalidate>
				<?php
				echo IWQ_Form::render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- IWQ_Form escapa su salida.
				echo IWQ_Recaptcha::render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- salida escapada en la clase.
				?>

				<?php
				$iwq_privacy = iwq_get_option( 'form_privacy_text' );

				if ( $iwq_privacy && function_exists( 'wc_replace_policy_page_link_placeholders' ) ) :
					?>
					<p class="iwq-field__description iwq-privacy">
						<?php echo wp_kses_post( wc_replace_policy_page_link_placeholders( $iwq_privacy ) ); ?>
					</p>
				<?php endif; ?>

				<button type="submit" class="iwq-add-button iwq-submit">
					<span class="iwq-add-button__spinner" aria-hidden="true"></span>
					<span class="iwq-add-button__label">
						<?php echo esc_html( iwq_get_option( 'submit_label', __( 'Enviar solicitud', 'imagina-woo-quotes' ) ) ); ?>
					</span>
				</button>
			</form>
		</div>

	<?php endif; ?>

	</div>

</div>
