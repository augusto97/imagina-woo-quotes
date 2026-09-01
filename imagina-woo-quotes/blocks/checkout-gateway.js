/**
 * Método de pago «Solicitar presupuesto» para el checkout de bloques.
 */
( function ( registry, element, htmlEntities, i18n ) {
	'use strict';

	var el = element.createElement;
	var settings = ( window.wc.wcSettings.getSetting( 'iwq_quote_data', {} ) ) || {};
	var label = htmlEntities.decodeEntities( settings.title || i18n.__( 'Solicitar presupuesto', 'imagina-woo-quotes' ) );

	/**
	 * Descripción que se muestra al elegir el método.
	 *
	 * @return {Object} Elemento de React.
	 */
	function Content() {
		return el( 'p', null, htmlEntities.decodeEntities( settings.description || '' ) );
	}

	registry.registerPaymentMethod( {
		name: 'iwq_quote',
		label: el( 'span', null, label ),
		content: el( Content, null ),
		edit: el( Content, null ),
		canMakePayment: function () {
			return true;
		},
		ariaLabel: label,
		supports: {
			features: settings.supports || [ 'products' ]
		}
	} );
}(
	window.wc.wcBlocksRegistry,
	window.wp.element,
	window.wp.htmlEntities,
	window.wp.i18n
) );
