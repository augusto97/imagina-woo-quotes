<?php
/**
 * Constructor de formularios.
 *
 * La lista se reordena arrastrando y cada campo se edita en su propio panel
 * plegable. El estado vive en el DOM y se envía como campos ocultos, así que
 * no hace falta ninguna llamada AJAX para guardar.
 *
 * @package ImaginaWooQuotes
 *
 * @var string $name   Nombre base de los campos.
 * @var array  $fields Campos configurados.
 */

defined( 'ABSPATH' ) || exit;

$iwq_types       = iwq_get_form_field_types();
$iwq_widths      = iwq_get_form_field_widths();
$iwq_connectable = iwq_get_connectable_fields();
?>
<div class="iwq-builder" data-name="<?php echo esc_attr( $name ); ?>">

	<p class="iwq-builder__intro">
		<?php esc_html_e( 'Arrastra los campos para reordenarlos. Los marcados como del sistema se pueden renombrar y desactivar, pero no borrar.', 'imagina-woo-quotes' ); ?>
	</p>

	<ul class="iwq-builder__list">
		<?php
		$iwq_index = 0;

		foreach ( $fields as $iwq_field ) :
			$iwq_base = $name . '[' . $iwq_index . ']';
			?>
			<li class="iwq-builder__item<?php echo 'yes' === $iwq_field['enabled'] ? '' : ' is-disabled'; ?>" data-index="<?php echo esc_attr( $iwq_index ); ?>">

				<div class="iwq-builder__header">
					<span class="iwq-builder__handle dashicons dashicons-menu" aria-hidden="true"></span>

					<span class="iwq-builder__title">
						<?php echo esc_html( $iwq_field['label'] ? $iwq_field['label'] : $iwq_field['id'] ); ?>
					</span>

					<span class="iwq-builder__type">
						<?php echo esc_html( isset( $iwq_types[ $iwq_field['type'] ] ) ? $iwq_types[ $iwq_field['type'] ]['label'] : $iwq_field['type'] ); ?>
					</span>

					<?php if ( $iwq_field['core'] ) : ?>
						<span class="iwq-builder__badge"><?php esc_html_e( 'Sistema', 'imagina-woo-quotes' ); ?></span>
					<?php endif; ?>

					<button type="button" class="iwq-btn iwq-btn--ghost iwq-btn--sm iwq-builder__toggle" aria-expanded="false">
						<?php esc_html_e( 'Editar', 'imagina-woo-quotes' ); ?>
					</button>

					<?php if ( ! $iwq_field['core'] ) : ?>
						<button type="button" class="iwq-btn iwq-btn--ghost iwq-btn--sm iwq-builder__delete" aria-label="<?php esc_attr_e( 'Borrar el campo', 'imagina-woo-quotes' ); ?>">
							<span class="dashicons dashicons-trash" aria-hidden="true"></span>
						</button>
					<?php endif; ?>
				</div>

				<div class="iwq-builder__body" hidden>
					<input type="hidden" name="<?php echo esc_attr( $iwq_base ); ?>[id]" value="<?php echo esc_attr( $iwq_field['id'] ); ?>">
					<input type="hidden" name="<?php echo esc_attr( $iwq_base ); ?>[core]" value="<?php echo $iwq_field['core'] ? '1' : ''; ?>">

					<p>
						<label><?php esc_html_e( 'Etiqueta', 'imagina-woo-quotes' ); ?><br>
							<input type="text" class="regular-text iwq-builder__label-input" name="<?php echo esc_attr( $iwq_base ); ?>[label]" value="<?php echo esc_attr( $iwq_field['label'] ); ?>">
						</label>
					</p>

					<p>
						<label><?php esc_html_e( 'Tipo', 'imagina-woo-quotes' ); ?><br>
							<select name="<?php echo esc_attr( $iwq_base ); ?>[type]" class="iwq-builder__type-select"<?php echo $iwq_field['core'] ? ' disabled' : ''; ?>>
								<?php foreach ( $iwq_types as $iwq_key => $iwq_type ) : ?>
									<option value="<?php echo esc_attr( $iwq_key ); ?>"<?php selected( $iwq_field['type'], $iwq_key ); ?>>
										<?php echo esc_html( $iwq_type['label'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<?php if ( $iwq_field['core'] ) : ?>
								<input type="hidden" name="<?php echo esc_attr( $iwq_base ); ?>[type]" value="<?php echo esc_attr( $iwq_field['type'] ); ?>">
							<?php endif; ?>
						</label>
					</p>

					<p>
						<label><?php esc_html_e( 'Marcador de posición', 'imagina-woo-quotes' ); ?><br>
							<input type="text" class="regular-text" name="<?php echo esc_attr( $iwq_base ); ?>[placeholder]" value="<?php echo esc_attr( $iwq_field['placeholder'] ); ?>">
						</label>
					</p>

					<p>
						<label><?php esc_html_e( 'Texto de ayuda', 'imagina-woo-quotes' ); ?><br>
							<input type="text" class="large-text" name="<?php echo esc_attr( $iwq_base ); ?>[description]" value="<?php echo esc_attr( $iwq_field['description'] ); ?>">
						</label>
					</p>

					<p class="iwq-builder__options"<?php echo in_array( $iwq_field['type'], array( 'select', 'multiselect', 'radio', 'checkbox' ), true ) ? '' : ' hidden'; ?>>
						<label><?php esc_html_e( 'Opciones (una por línea)', 'imagina-woo-quotes' ); ?><br>
							<textarea class="large-text code" rows="4" name="<?php echo esc_attr( $iwq_base ); ?>[options_raw]"><?php
							$iwq_lines = array();

							foreach ( (array) $iwq_field['options'] as $iwq_option ) {
								$iwq_lines[] = is_array( $iwq_option ) ? $iwq_option['label'] : $iwq_option;
							}

							echo esc_textarea( implode( "\n", $iwq_lines ) );
							?></textarea>
						</label>
					</p>

					<p class="iwq-builder__file"<?php echo 'file' === $iwq_field['type'] ? '' : ' hidden'; ?>>
						<label><?php esc_html_e( 'Extensiones admitidas', 'imagina-woo-quotes' ); ?><br>
							<input type="text" class="regular-text" name="<?php echo esc_attr( $iwq_base ); ?>[extensions]" value="<?php echo esc_attr( $iwq_field['extensions'] ); ?>" placeholder="pdf, jpg, png">
						</label>
					</p>

					<p>
						<label><?php esc_html_e( 'Ancho', 'imagina-woo-quotes' ); ?><br>
							<select name="<?php echo esc_attr( $iwq_base ); ?>[width]">
								<?php foreach ( $iwq_widths as $iwq_key => $iwq_label ) : ?>
									<option value="<?php echo esc_attr( $iwq_key ); ?>"<?php selected( $iwq_field['width'], $iwq_key ); ?>>
										<?php echo esc_html( $iwq_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</label>
					</p>

					<p>
						<label><?php esc_html_e( 'Guardar en el pedido como', 'imagina-woo-quotes' ); ?><br>
							<select name="<?php echo esc_attr( $iwq_base ); ?>[connect_to]">
								<?php foreach ( $iwq_connectable as $iwq_key => $iwq_label ) : ?>
									<option value="<?php echo esc_attr( $iwq_key ); ?>"<?php selected( $iwq_field['connect_to'], $iwq_key ); ?>>
										<?php echo esc_html( $iwq_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</label>
					</p>

					<p>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $iwq_base ); ?>[required]" value="1"<?php checked( $iwq_field['required'], 'yes' ); ?>>
							<?php esc_html_e( 'Obligatorio', 'imagina-woo-quotes' ); ?>
						</label>
						&nbsp;
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $iwq_base ); ?>[enabled]" value="1" class="iwq-builder__enabled"<?php checked( $iwq_field['enabled'], 'yes' ); ?>>
							<?php esc_html_e( 'Activo', 'imagina-woo-quotes' ); ?>
						</label>
					</p>
				</div>
			</li>
			<?php
			++$iwq_index;
		endforeach;
		?>
	</ul>

	<button type="button" class="iwq-btn iwq-btn--secondary iwq-builder__add">
		<?php esc_html_e( 'Añadir campo', 'imagina-woo-quotes' ); ?>
	</button>
</div>
