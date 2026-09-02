<?php
/**
 * Botones de acción.
 *
 * @package ImaginaWooQuotes
 *
 * @var array $buttons Lista de {url, label, primary}.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $buttons ) ) {
	return;
}
?>
<table class="iwq-cta" cellpadding="0" cellspacing="0" role="presentation">
	<tr>
		<?php foreach ( $buttons as $iwq_button ) : ?>
			<td>
				<a class="iwq-btn <?php echo ! empty( $iwq_button['primary'] ) ? 'iwq-btn-primary' : 'iwq-btn-secondary'; ?>" href="<?php echo esc_url( $iwq_button['url'] ); ?>">
					<?php echo esc_html( $iwq_button['label'] ); ?>
				</a>
			</td>
		<?php endforeach; ?>
	</tr>
</table>
