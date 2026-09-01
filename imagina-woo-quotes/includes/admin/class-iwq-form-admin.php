<?php
/**
 * Piezas del constructor de formularios que viven en el administrador.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Form_Admin
 */
class IWQ_Form_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_footer', array( $this, 'print_field_template' ) );
		add_action( 'update_option_iwq_form_fields', array( $this, 'flush_caches' ) );
	}

	/**
	 * Imprime la plantilla que el JavaScript clona al añadir un campo.
	 *
	 * Usar una plantilla en PHP en lugar de construir el HTML en JavaScript
	 * mantiene una sola fuente de verdad para el marcado y las traducciones.
	 *
	 * @return void
	 */
	public function print_field_template() {
		$screen = get_current_screen();

		if ( ! $screen || false === strpos( $screen->id, 'iwq-settings' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo determina la pestaña visible.
		if ( ! isset( $_GET['tab'] ) || 'form' !== $_GET['tab'] ) {
			return;
		}
		?>
		<script type="text/html" id="tmpl-iwq-builder-field">
			<?php
			iwq_get_template(
				'admin/form-builder-field.php',
				array(
					'types'       => iwq_get_form_field_types(),
					'widths'      => iwq_get_form_field_widths(),
					'connectable' => iwq_get_connectable_fields(),
				)
			);
			?>
		</script>
		<?php
	}

	/**
	 * Invalida las cachés que dependen de la definición del formulario.
	 *
	 * @return void
	 */
	public function flush_caches() {
		/**
		 * Se dispara al guardar los ajustes del plugin.
		 */
		do_action( 'iwq_settings_saved' );
	}
}
