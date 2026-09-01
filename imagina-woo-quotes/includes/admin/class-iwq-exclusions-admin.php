<?php
/**
 * Ajustes de presupuesto en la ficha de cada producto.
 *
 * Vive en la pestaña «General» de los datos del producto, junto al precio,
 * que es donde el comerciante espera encontrarlo.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Exclusions_Admin
 */
class IWQ_Exclusions_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'render_field' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_field' ) );
	}

	/**
	 * Pinta el selector de modo.
	 *
	 * @return void
	 */
	public function render_field() {
		echo '<div class="options_group show_if_simple show_if_variable show_if_external show_if_grouped">';

		woocommerce_wp_select(
			array(
				'id'          => '_iwq_mode',
				'label'       => __( 'Presupuestos', 'imagina-woo-quotes' ),
				'description' => __( '«Solo presupuesto» oculta el precio y el botón de compra de este producto.', 'imagina-woo-quotes' ),
				'desc_tip'    => true,
				'options'     => array(
					'inherit'  => __( 'Según los ajustes generales', 'imagina-woo-quotes' ),
					'enabled'  => __( 'Permitir presupuesto', 'imagina-woo-quotes' ),
					'only'     => __( 'Solo presupuesto', 'imagina-woo-quotes' ),
					'disabled' => __( 'Nunca presupuestar', 'imagina-woo-quotes' ),
				),
			)
		);

		global $post;

		$count = $post ? (int) get_post_meta( $post->ID, '_iwq_request_count', true ) : 0;

		if ( $count ) {
			printf(
				'<p class="form-field"><span class="description">%s</span></p>',
				esc_html(
					sprintf(
						/* translators: %s: número de solicitudes. */
						_n(
							'Este producto se ha pedido en %s presupuesto.',
							'Este producto se ha pedido en %s presupuestos.',
							$count,
							'imagina-woo-quotes'
						),
						number_format_i18n( $count )
					)
				)
			);
		}

		echo '</div>';
	}

	/**
	 * Guarda el modo elegido.
	 *
	 * @param WC_Product $product Producto que se está guardando.
	 * @return void
	 */
	public function save_field( $product ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce ya validó el nonce del producto.
		$mode = isset( $_POST['_iwq_mode'] ) ? sanitize_key( wp_unslash( $_POST['_iwq_mode'] ) ) : 'inherit';

		if ( ! in_array( $mode, array( 'inherit', 'enabled', 'only', 'disabled' ), true ) ) {
			$mode = 'inherit';
		}

		$product->update_meta_data( IWQ_Exclusions::META_MODE, $mode );
	}
}
