/**
 * Bloques del editor para las plantillas de PDF.
 *
 * Se escribe con `wp.element.createElement` en lugar de JSX a propósito: así
 * el plugin no necesita npm, webpack ni un paso de compilación, y el archivo
 * que se publica es exactamente el que se lee en el repositorio.
 */
( function ( blocks, element, blockEditor, components, i18n ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var ToggleControl = components.ToggleControl;

	/**
	 * Marca de posición que se ve en el editor.
	 *
	 * @param {string} title Título del bloque.
	 * @param {string} help  Explicación de qué saldrá en el PDF.
	 * @return {Object} Elemento de React.
	 */
	function preview( title, help ) {
		return el(
			'div',
			{ className: 'iwq-block-preview' },
			el( 'strong', { className: 'iwq-block-preview__title' }, title ),
			el( 'span', { className: 'iwq-block-preview__help' }, help )
		);
	}

	/**
	 * Registra un bloque sencillo, sin ajustes.
	 *
	 * @param {string} name  Nombre sin espacio de nombres.
	 * @param {string} title Título visible.
	 * @param {string} help  Texto de ayuda.
	 * @param {string} icon  Icono de Dashicons.
	 */
	function registerSimple( name, title, help, icon ) {
		blocks.registerBlockType( 'imagina-quotes/' + name, {
			apiVersion: 3,
			title: title,
			description: help,
			icon: icon,
			category: 'design',
			supports: { html: false, multiple: false },
			edit: function () {
				return el( 'div', useBlockProps(), preview( title, help ) );
			},
			save: function () {
				return null;
			}
		} );
	}

	registerSimple(
		'quote-totals',
		__( 'Totales del presupuesto', 'imagina-woo-quotes' ),
		__( 'Subtotal, impuestos, envío y total.', 'imagina-woo-quotes' ),
		'calculator'
	);

	registerSimple(
		'customer-info',
		__( 'Datos del cliente', 'imagina-woo-quotes' ),
		__( 'Dirección de facturación, email y teléfono.', 'imagina-woo-quotes' ),
		'businessperson'
	);

	registerSimple(
		'quote-meta',
		__( 'Datos del presupuesto', 'imagina-woo-quotes' ),
		__( 'Número, fecha y vencimiento.', 'imagina-woo-quotes' ),
		'info-outline'
	);

	registerSimple(
		'form-data',
		__( 'Respuestas del formulario', 'imagina-woo-quotes' ),
		__( 'Lo que el cliente escribió al solicitar el presupuesto.', 'imagina-woo-quotes' ),
		'feedback'
	);

	registerSimple(
		'quote-actions',
		__( 'Botones de respuesta', 'imagina-woo-quotes' ),
		__( 'Enlaces para aceptar o rechazar el presupuesto.', 'imagina-woo-quotes' ),
		'yes-alt'
	);

	registerSimple(
		'store-logo',
		__( 'Logotipo de la tienda', 'imagina-woo-quotes' ),
		__( 'El logo configurado en los ajustes del PDF.', 'imagina-woo-quotes' ),
		'format-image'
	);

	// La tabla de productos sí tiene ajustes propios.
	blocks.registerBlockType( 'imagina-quotes/quote-table', {
		apiVersion: 3,
		title: __( 'Tabla de productos', 'imagina-woo-quotes' ),
		description: __( 'Las líneas del presupuesto con cantidades y precios.', 'imagina-woo-quotes' ),
		icon: 'editor-table',
		category: 'design',
		supports: { html: false, multiple: false },
		attributes: {
			showImages: { type: 'boolean', default: true },
			showSku: { type: 'boolean', default: false }
		},
		edit: function ( props ) {
			return el(
				element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Columnas', 'imagina-woo-quotes' ) },
						el( ToggleControl, {
							label: __( 'Mostrar imágenes', 'imagina-woo-quotes' ),
							checked: props.attributes.showImages,
							onChange: function ( value ) {
								props.setAttributes( { showImages: value } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Mostrar SKU', 'imagina-woo-quotes' ),
							checked: props.attributes.showSku,
							onChange: function ( value ) {
								props.setAttributes( { showSku: value } );
							}
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					preview(
						__( 'Tabla de productos', 'imagina-woo-quotes' ),
						__( 'Las líneas del presupuesto con cantidades y precios.', 'imagina-woo-quotes' )
					)
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
	window.wp.i18n
) );
