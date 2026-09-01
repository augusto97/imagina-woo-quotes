<?php
/**
 * Shortcodes del plugin.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Shortcodes
 */
class IWQ_Shortcodes {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'iwq_quote_list', array( $this, 'quote_list' ) );
		add_shortcode( 'iwq_quote_count', array( $this, 'quote_count' ) );
		add_shortcode( 'iwq_quote_button', array( $this, 'quote_button' ) );
	}

	/**
	 * `[iwq_quote_list]` — lista de productos y formulario de solicitud.
	 *
	 * @return string
	 */
	public function quote_list() {
		IWQ_Frontend::require_assets();

		return iwq_get_template(
			'quote/quote-page.php',
			array( 'items' => IWQ_Session::get_items() ),
			true
		);
	}

	/**
	 * `[iwq_quote_count]` — contador con enlace al panel.
	 *
	 * @param array $atts Atributos del shortcode.
	 * @return string
	 */
	public function quote_count( $atts ) {
		$atts = shortcode_atts(
			array(
				'label' => __( 'Presupuesto', 'imagina-woo-quotes' ),
				'icon'  => 'yes',
			),
			$atts,
			'iwq_quote_count'
		);

		IWQ_Frontend::require_assets();

		$count = IWQ_Session::count();

		$icon = 'yes' === $atts['icon']
			? '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false"><path d="M4 4h2l2 9h7l2-6H7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="16" r="1.2" fill="currentColor"/><circle cx="15" cy="16" r="1.2" fill="currentColor"/></svg>'
			: '';

		return sprintf(
			'<button type="button" class="iwq iwq-open-drawer">%1$s<span class="iwq-open-drawer__label">%2$s</span><span class="iwq-count"%3$s>%4$d</span></button>',
			$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG literal.
			esc_html( $atts['label'] ),
			$count ? '' : ' hidden',
			$count
		);
	}

	/**
	 * `[iwq_quote_button]` — botón para un producto concreto.
	 *
	 * @param array $atts Atributos del shortcode.
	 * @return string
	 */
	public function quote_button( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'iwq_quote_button' );

		$product_id = absint( $atts['id'] );

		if ( ! $product_id ) {
			global $product;
			$product_id = $product instanceof WC_Product ? $product->get_id() : 0;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product || ! IWQ_Exclusions::is_quotable( $product ) ) {
			return '';
		}

		IWQ_Frontend::require_assets();

		return iwq_get_template(
			'quote/add-to-quote-button.php',
			array(
				'product'     => $product,
				'context'     => 'shortcode',
				'in_list'     => IWQ_Session::has_product( $product->get_id() ),
				'is_variable' => $product->is_type( 'variable' ),
			),
			true
		);
	}
}
