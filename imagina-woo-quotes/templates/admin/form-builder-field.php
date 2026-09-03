<?php
/**
 * Plantilla de un campo nuevo del constructor.
 *
 * El JavaScript la clona y sustituye `__INDEX__` por la posición real.
 *
 * @package ImaginaWooQuotes
 *
 * @var array $types       Tipos de campo disponibles.
 * @var array $widths      Anchos disponibles.
 * @var array $connectable Propiedades del pedido enlazables.
 */

defined( 'ABSPATH' ) || exit;
?>
<li class="iwq-builder__item" data-index="__INDEX__">

	<div class="iwq-builder__header">
		<span class="iwq-builder__handle dashicons dashicons-menu" aria-hidden="true"></span>
		<span class="iwq-builder__title"><?php esc_html_e( 'Campo nuevo', 'imagina-woo-quotes' ); ?></span>
		<span class="iwq-builder__type"><?php esc_html_e( 'Texto', 'imagina-woo-quotes' ); ?></span>

		<button type="button" class="iwq-btn iwq-btn--ghost iwq-btn--sm iwq-builder__toggle" aria-expanded="true">
			<?php esc_html_e( 'Editar', 'imagina-woo-quotes' ); ?>
		</button>

		<button type="button" class="iwq-btn iwq-btn--ghost iwq-btn--sm iwq-builder__delete" aria-label="<?php esc_attr_e( 'Borrar el campo', 'imagina-woo-quotes' ); ?>">
			<span class="dashicons dashicons-trash" aria-hidden="true"></span>
		</button>
	</div>

	<div class="iwq-builder__body">
		<input type="hidden" name="__NAME__[__INDEX__][id]" value="__ID__">
		<input type="hidden" name="__NAME__[__INDEX__][core]" value="">

		<p>
			<label><?php esc_html_e( 'Etiqueta', 'imagina-woo-quotes' ); ?><br>
				<input type="text" class="regular-text iwq-builder__label-input" name="__NAME__[__INDEX__][label]" value="">
			</label>
		</p>

		<p>
			<label><?php esc_html_e( 'Tipo', 'imagina-woo-quotes' ); ?><br>
				<select name="__NAME__[__INDEX__][type]" class="iwq-builder__type-select">
					<?php foreach ( $types as $iwq_key => $iwq_type ) : ?>
						<option value="<?php echo esc_attr( $iwq_key ); ?>"><?php echo esc_html( $iwq_type['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</p>

		<p>
			<label><?php esc_html_e( 'Marcador de posición', 'imagina-woo-quotes' ); ?><br>
				<input type="text" class="regular-text" name="__NAME__[__INDEX__][placeholder]" value="">
			</label>
		</p>

		<p>
			<label><?php esc_html_e( 'Texto de ayuda', 'imagina-woo-quotes' ); ?><br>
				<input type="text" class="large-text" name="__NAME__[__INDEX__][description]" value="">
			</label>
		</p>

		<p class="iwq-builder__options" hidden>
			<label><?php esc_html_e( 'Opciones (una por línea)', 'imagina-woo-quotes' ); ?><br>
				<textarea class="large-text code" rows="4" name="__NAME__[__INDEX__][options_raw]"></textarea>
			</label>
		</p>

		<p class="iwq-builder__file" hidden>
			<label><?php esc_html_e( 'Extensiones admitidas', 'imagina-woo-quotes' ); ?><br>
				<input type="text" class="regular-text" name="__NAME__[__INDEX__][extensions]" value="" placeholder="pdf, jpg, png">
			</label>
		</p>

		<p>
			<label><?php esc_html_e( 'Ancho', 'imagina-woo-quotes' ); ?><br>
				<select name="__NAME__[__INDEX__][width]">
					<?php foreach ( $widths as $iwq_key => $iwq_label ) : ?>
						<option value="<?php echo esc_attr( $iwq_key ); ?>"><?php echo esc_html( $iwq_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</p>

		<p>
			<label><?php esc_html_e( 'Guardar en el pedido como', 'imagina-woo-quotes' ); ?><br>
				<select name="__NAME__[__INDEX__][connect_to]">
					<?php foreach ( $connectable as $iwq_key => $iwq_label ) : ?>
						<option value="<?php echo esc_attr( $iwq_key ); ?>"><?php echo esc_html( $iwq_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</p>

		<p>
			<label>
				<input type="checkbox" name="__NAME__[__INDEX__][required]" value="1">
				<?php esc_html_e( 'Obligatorio', 'imagina-woo-quotes' ); ?>
			</label>
			&nbsp;
			<label>
				<input type="checkbox" name="__NAME__[__INDEX__][enabled]" value="1" class="iwq-builder__enabled" checked>
				<?php esc_html_e( 'Activo', 'imagina-woo-quotes' ); ?>
			</label>
		</p>
	</div>
</li>
