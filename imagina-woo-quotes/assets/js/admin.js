/**
 * Imagina Woo Quotes — administración.
 *
 * Solo se carga en la página de ajustes del plugin.
 */
( function ( $ ) {
	'use strict';

	var settings = window.iwqAdmin || {};
	var i18n = settings.i18n || {};

	/* ------------------------------------------------------------------
	 * Constructor de formularios
	 * --------------------------------------------------------------- */

	var $builder = $( '.iwq-builder' );

	if ( $builder.length ) {
		var $list = $builder.find( '.iwq-builder__list' );

		$list.sortable( {
			handle: '.iwq-builder__handle',
			placeholder: 'iwq-builder__placeholder',
			forcePlaceholderSize: true,
			update: reindex
		} );

		// Abrir y cerrar el panel de un campo.
		$builder.on( 'click', '.iwq-builder__toggle', function () {
			var $body = $( this ).closest( '.iwq-builder__item' ).find( '.iwq-builder__body' );
			var isOpen = ! $body.prop( 'hidden' );

			$body.prop( 'hidden', isOpen );
			$( this ).attr( 'aria-expanded', ! isOpen );
		} );

		// El título del encabezado sigue a la etiqueta mientras se escribe.
		$builder.on( 'input', '.iwq-builder__label-input', function () {
			var value = $( this ).val();

			$( this )
				.closest( '.iwq-builder__item' )
				.find( '.iwq-builder__title' )
				.text( value || i18n.newField );
		} );

		// Cambiar el tipo muestra u oculta los ajustes que le corresponden.
		$builder.on( 'change', '.iwq-builder__type-select', function () {
			var type = $( this ).val();
			var $item = $( this ).closest( '.iwq-builder__item' );
			var hasOptions = [ 'select', 'multiselect', 'radio', 'checkbox' ].indexOf( type ) !== -1;

			$item.find( '.iwq-builder__options' ).prop( 'hidden', ! hasOptions );
			$item.find( '.iwq-builder__file' ).prop( 'hidden', type !== 'file' );

			var label = settings.fieldTypes && settings.fieldTypes[ type ]
				? settings.fieldTypes[ type ].label
				: type;

			$item.find( '.iwq-builder__type' ).text( label );
		} );

		// Un campo desactivado se atenúa, para verlo de un vistazo.
		$builder.on( 'change', '.iwq-builder__enabled', function () {
			$( this )
				.closest( '.iwq-builder__item' )
				.toggleClass( 'is-disabled', ! $( this ).prop( 'checked' ) );
		} );

		$builder.on( 'click', '.iwq-builder__delete', function () {
			if ( ! window.confirm( i18n.confirmDelete ) ) {
				return;
			}

			$( this ).closest( '.iwq-builder__item' ).remove();
			reindex();
		} );

		$builder.on( 'click', '.iwq-builder__add', function () {
			var template = $( '#tmpl-iwq-builder-field' ).html();

			if ( ! template ) {
				return;
			}

			var index = $list.children().length;
			var id = 'field_' + Date.now().toString( 36 );

			var html = template
				.split( '__NAME__' ).join( $builder.data( 'name' ) )
				.split( '__INDEX__' ).join( index )
				.split( '__ID__' ).join( id );

			$list.append( html );
			$list.children().last()[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'center' } );
		} );
	}

	/**
	 * Renumera los campos tras reordenar o borrar, para que los nombres del
	 * formulario sigan siendo consecutivos.
	 */
	function reindex() {
		$( '.iwq-builder__list' ).children().each( function ( index ) {
			$( this ).attr( 'data-index', index );

			$( this ).find( '[name]' ).each( function () {
				var name = $( this ).attr( 'name' );

				$( this ).attr( 'name', name.replace( /\[\d+\]/, '[' + index + ']' ) );
			} );
		} );
	}

	/* ------------------------------------------------------------------
	 * Selector de imagen
	 * --------------------------------------------------------------- */

	$( document ).on( 'click', '.iwq-media-select', function ( event ) {
		event.preventDefault();

		var $field = $( this ).closest( '.iwq-media-field' );

		var frame = window.wp.media( {
			title: $( this ).text(),
			multiple: false,
			library: { type: 'image' }
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var url = attachment.sizes && attachment.sizes.medium
				? attachment.sizes.medium.url
				: attachment.url;

			$field.find( 'input[type="hidden"]' ).val( attachment.id );
			$field.find( '.iwq-media-field__preview' ).html( '<img src="' + url + '" alt="">' );
			$field.find( '.iwq-media-clear' ).prop( 'hidden', false );
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.iwq-media-clear', function ( event ) {
		event.preventDefault();

		var $field = $( this ).closest( '.iwq-media-field' );

		$field.find( 'input[type="hidden"]' ).val( '' );
		$field.find( '.iwq-media-field__preview' ).empty();
		$( this ).prop( 'hidden', true );
	} );
}( window.jQuery ) );
