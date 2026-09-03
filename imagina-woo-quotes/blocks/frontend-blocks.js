/**
 * Bloques del front: contador y lista de presupuesto.
 */
( function ( blocks, element, blockEditor, components, i18n, ServerSideRender ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;

	blocks.registerBlockType( 'imagina-quotes/quote-count', {
		apiVersion: 3,
		title: __( 'Contador de presupuesto', 'imagina-woo-quotes' ),
		description: __( 'Botón con el número de productos de la lista; abre el panel lateral.', 'imagina-woo-quotes' ),
		icon: 'clipboard',
		category: 'woocommerce',
		attributes: {
			label: { type: 'string', default: '' },
			showIcon: { type: 'boolean', default: true }
		},
		edit: function ( props ) {
			return el(
				element.Fragment,
				null,
				el(
					blockEditor.InspectorControls,
					null,
					el(
						components.PanelBody,
						{ title: __( 'Ajustes', 'imagina-woo-quotes' ) },
						el( components.TextControl, {
							label: __( 'Texto del botón', 'imagina-woo-quotes' ),
							value: props.attributes.label,
							onChange: function ( value ) {
								props.setAttributes( { label: value } );
							}
						} ),
						el( components.ToggleControl, {
							label: __( 'Mostrar icono', 'imagina-woo-quotes' ),
							checked: props.attributes.showIcon,
							onChange: function ( value ) {
								props.setAttributes( { showIcon: value } );
							}
						} )
					)
				),
				el(
					'div',
					blockEditor.useBlockProps(),
					el( ServerSideRender, {
						block: 'imagina-quotes/quote-count',
						attributes: props.attributes
					} )
				)
			);
		},
		save: function () {
			return null;
		}
	} );

	blocks.registerBlockType( 'imagina-quotes/quote-list', {
		apiVersion: 3,
		title: __( 'Lista de presupuesto', 'imagina-woo-quotes' ),
		description: __( 'Los productos de la lista y el formulario de solicitud.', 'imagina-woo-quotes' ),
		icon: 'list-view',
		category: 'woocommerce',
		supports: { multiple: false, align: [ 'wide', 'full' ] },
		attributes: { align: { type: 'string' } },
		edit: function () {
			return el(
				'div',
				blockEditor.useBlockProps(),
				el(
					components.Placeholder,
					{
						icon: 'list-view',
						label: __( 'Lista de presupuesto', 'imagina-woo-quotes' ),
						instructions: __( 'Aquí se mostrarán los productos que el visitante haya añadido y el formulario de solicitud.', 'imagina-woo-quotes' )
					}
				)
			);
		},
		save: function () {
			return null;
		}
	} );
}(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n,
	window.wp.serverSideRender
) );
